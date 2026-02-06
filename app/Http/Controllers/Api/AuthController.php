<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            Fortify::username() => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = md5('login'.implode('|', [$request->{Fortify::username()}, $request->ip()]));

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ], 429);
        }

        $user = User::where(Fortify::username(), $request->{Fortify::username()})->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // Check 2FA
        if ($user->two_factor_secret &&
            $user->two_factor_confirmed_at) {
            return response()->json([
                'two_factor' => true,
                'user_id' => $user->id,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function twoFactorChallenge(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($request->recovery_code) {
            if (! $user->validRecoveryCode($request->recovery_code)) {
                throw ValidationException::withMessages([
                    'recovery_code' => [__('The provided recovery code was invalid.')],
                ]);
            }

            $user->replaceRecoveryCode($request->recovery_code);
        } elseif ($request->code) {
            if (! app(TwoFactorAuthenticationProvider::class)->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $request->code
            )) {
                throw ValidationException::withMessages([
                    'code' => [__('The provided two factor authentication code was invalid.')],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'code' => [__('Please provide a code or recovery code.')],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function register(Request $request, CreatesNewUsers $creator)
    {
        $user = $creator->create($request->all());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request)
    {
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function confirmPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => [trans('auth.password')],
            ]);
        }

        return response()->json([
            'message' => 'Password confirmed',
        ]);
    }
}
