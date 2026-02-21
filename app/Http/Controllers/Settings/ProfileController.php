<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profileService) {}

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $result = $this->profileService->updateProfile($request->user(), $request->validated());

        return response()->json(
            [
                'message' => $result['message'],
                'user' => $result['user'] ?? null,
            ],
            $result['success'] ? 200 : 500
        );
    }

    public function cancelPendingEmail(Request $request): JsonResponse
    {
        $this->profileService->cancelPendingEmail($request->user());

        return response()->json([
            'message' => __('messages.pending_email_cancelled'),
            'user' => $request->user(),
        ]);
    }

    public function destroy(ProfileDeleteRequest $request): JsonResponse
    {
        $this->profileService->deleteUserAndTokens($request->user(), $request->user()->currentAccessToken());

        return response()->json([
            'message' => __('messages.account_deleted'),
        ]);
    }
}
