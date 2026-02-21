<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\TwoFactorChallengeRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attemptLogin(
            $request->email,
            $request->password,
            $request->ip()
        );

        return response()->json($result);
    }

    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $result = $this->authService->handleTwoFactorChallenge(
            $request->user_id,
            $request->code,
            $request->recovery_code
        );

        return response()->json($result);
    }

    public function register(RegisterRequest $request, CreatesNewUsers $creator): JsonResponse
    {
        $result = $this->authService->register($request->validated(), $creator);

        return response()->json($result, 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => __('messages.logged_out')]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
        $this->authService->confirmPassword($request->user(), $request->password);

        return response()->json(['message' => __('messages.password_confirmed')]);
    }
}
