<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->user(), $request->password);

        return response()->json([
            'message' => __('messages.password_updated'),
        ]);
    }
}
