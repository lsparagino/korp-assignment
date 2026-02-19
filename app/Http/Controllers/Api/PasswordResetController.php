<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;

class PasswordResetController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker(config('fortify.passwords'))->sendResetLink(
            $request->only(Fortify::email())
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetsUserPasswords $resets): JsonResponse
    {
        $status = Password::broker(config('fortify.passwords'))->reset(
            $request->only(Fortify::email(), 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($resets, $request) {
                $resets->reset($user, $request->only('password', 'password_confirmation'));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }
}
