<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Throwable;

class AuthService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * @return array{two_factor?: bool, user_id?: int, access_token?: string, token_type?: string, user?: User}
     */
    public function attemptLogin(string $username, string $password, string $ip): array
    {
        $throttleKey = hash('xxh128', 'login'.implode('|', [$username, $ip]));

        $this->checkThrottling($throttleKey);

        $user = User::where(Fortify::username(), $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);

            Log::info('Failed login attempt', ['username' => $username, 'ip' => $ip]);

            $severity = $user && in_array($user->role, [UserRole::Admin, UserRole::Manager])
                ? AuditSeverity::High
                : AuditSeverity::Medium;

            $this->auditService->log(
                AuditCategory::Auth,
                $severity,
                'user.login_failed',
                __('messages.audit.user_login_failed'),
                ['user_id' => $user?->id, 'user_name' => $user?->name, 'company_id' => 0, 'metadata' => ['username' => $username]],
            );

            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($throttleKey);

        Log::info('Successful login', ['user_id' => $user->id]);

        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Normal,
            'user.login',
            __('messages.audit.user_login'),
            ['user_id' => $user->id, 'user_name' => $user->name, 'company_id' => 0],
        );

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            return [
                'two_factor' => true,
                'user_id' => $user->id,
            ];
        }

        return $user->createAuthTokenResponse();
    }

    /**
     * @return array{access_token: string, token_type: string, user: User}
     */
    public function handleTwoFactorChallenge(int $userId, ?string $code, ?string $recoveryCode): array
    {
        $user = User::findOrFail($userId);

        if ($recoveryCode) {
            $this->verifyRecoveryCode($user, $recoveryCode);
        } elseif ($code) {
            $this->verifyTwoFactorCode($user, $code);
        } else {
            throw ValidationException::withMessages([
                'code' => [__('messages.two_factor_code_or_recovery_required')],
            ]);
        }

        return $user->createAuthTokenResponse();
    }

    /**
     * @return array{access_token: string, token_type: string, user: User}
     */
    public function register(array $data, CreatesNewUsers $creator): array
    {
        $user = $creator->create($data);

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);
            $user->delete();

            throw new HttpResponseException(response()->json([
                'message' => __('messages.verification_email_failed'),
            ], 500));
        }

        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Normal,
            'user.registered',
            __('messages.audit.user_registered'),
            ['user_id' => $user->id, 'user_name' => $user->name, 'company_id' => 0],
        );

        return $user->createAuthTokenResponse();
    }

    public function logout(User $user): void
    {
        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Normal,
            'user.logout',
            __('messages.audit.user_logout'),
            ['company_id' => 0],
        );

        $user->currentAccessToken()?->delete();
    }

    public function confirmPassword(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }
    }

    public function confirmTwoFactor(User $user, string $code, ConfirmTwoFactorAuthentication $confirm): void
    {
        $confirm($user, $code);

        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Medium,
            'user.2fa_enabled',
            __('messages.audit.user_2fa_enabled'),
            ['company_id' => 0],
        );
    }

    public function disableTwoFactor(User $user, DisableTwoFactorAuthentication $disable): void
    {
        $disable($user);

        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Medium,
            'user.2fa_disabled',
            __('messages.audit.user_2fa_disabled'),
            ['company_id' => 0],
        );
    }

    public function changePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);

        $this->auditService->log(
            AuditCategory::Auth,
            AuditSeverity::Medium,
            'user.password_changed',
            __('messages.audit.user_password_changed'),
            ['company_id' => 0],
        );
    }

    private function checkThrottling(string $throttleKey): void
    {
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new HttpResponseException(response()->json([
                'message' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ], 429));
        }
    }

    private function verifyRecoveryCode(User $user, string $recoveryCode): void
    {
        if (! $user->validRecoveryCode($recoveryCode)) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('messages.invalid_recovery_code')],
            ]);
        }

        $user->replaceRecoveryCode($recoveryCode);
    }

    private function verifyTwoFactorCode(User $user, string $code): void
    {
        if (! app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $code
        )) {
            throw ValidationException::withMessages([
                'code' => [__('messages.invalid_two_factor_code')],
            ]);
        }
    }
}
