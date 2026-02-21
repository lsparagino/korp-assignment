<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;

class PasswordResetController extends Controller
{
    public function __construct(private PasswordResetService $passwordResetService) {}

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->passwordResetService->sendPasswordResetLink(
            $request->only(Fortify::email())
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetsUserPasswords $resets): JsonResponse
    {
        $status = $this->passwordResetService->resetPassword(
            $request->only(Fortify::email(), 'password', 'password_confirmation', 'token'),
            $resets
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }
}
