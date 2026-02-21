<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Throwable;

class AuthService
{
    /**
     * @return array{two_factor?: bool, user_id?: int, access_token?: string, token_type?: string, user?: User}
     */
    public function attemptLogin(string $username, string $password, string $ip): array
    {
        $throttleKey = md5('login'.implode('|', [$username, $ip]));

        $this->checkThrottling($throttleKey);

        $user = User::where(Fortify::username(), $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);

            Log::info('Failed login attempt', ['username' => $username, 'ip' => $ip]);

            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($throttleKey);

        Log::info('Successful login', ['user_id' => $user->id]);

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

        return $user->createAuthTokenResponse();
    }

    public function logout(User $user): void
    {
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }

    public function confirmPassword(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [trans('auth.password')],
            ]);
        }
    }

    private function checkThrottling(string $throttleKey): void
    {
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new HttpResponseException(response()->json([
                'message' => trans('auth.throttle', [
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
