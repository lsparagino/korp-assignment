<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class AuthService
{
    /**
     * Attempt to authenticate a user and return the result.
     *
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    /**
     * Handle a two-factor authentication challenge.
     *
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
                'code' => [__('Please provide a code or recovery code.')],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }
    }

    /**
     * @throws HttpResponseException
     */
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

    /**
     * @throws ValidationException
     */
    private function verifyRecoveryCode(User $user, string $recoveryCode): void
    {
        if (! $user->validRecoveryCode($recoveryCode)) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('The provided recovery code was invalid.')],
            ]);
        }

        $user->replaceRecoveryCode($recoveryCode);
    }

    /**
     * @throws ValidationException
     */
    private function verifyTwoFactorCode(User $user, string $code): void
    {
        if (! app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $code
        )) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }
    }
}
