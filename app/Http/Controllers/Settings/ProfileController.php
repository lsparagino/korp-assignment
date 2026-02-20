<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $user->pending_email = $validated['email'];
            $user->save();

            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
                $user->update(['pending_email' => null]);

                return response()->json([
                    'message' => 'There was a problem sending the verification email. Please try again.',
                ], 500);
            }

            unset($validated['email']);
        }

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => $user->pending_email
                ? 'Profile updated. A verification link has been sent to your new email address.'
                : 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Cancel a pending email change.
     */
    public function cancelPendingEmail(): JsonResponse
    {
        $user = auth()->user();
        $user->update(['pending_email' => null]);

        return response()->json([
            'message' => 'Pending email change cancelled',
            'user' => $user,
        ]);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            if ($request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }

            $user->delete();
        });

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
