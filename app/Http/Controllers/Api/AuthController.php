<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\TwoFactorChallengeRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attemptLogin(
            $request->{Fortify::username()},
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

    public function register(Request $request, CreatesNewUsers $creator): JsonResponse
    {
        $user = $creator->create($request->all());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
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
