<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    public function updateProfile(User $user, array $validated): array
    {
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $user->pending_email = $validated['email'];
            $user->save();

            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
                $user->update(['pending_email' => null]);
                Log::error('Failed to send verification email during profile update', ['error' => $e->getMessage(), 'user_id' => $user->id]);

                return ['success' => false, 'message' => __('messages.verification_email_failed')];
            }

            unset($validated['email']);
        }

        $user->fill($validated);
        $user->save();

        Log::info('Profile updated', ['user_id' => $user->id, 'pending_email' => $user->pending_email]);

        return [
            'success' => true,
            'message' => $user->pending_email
                ? __('messages.profile_updated_pending_email')
                : __('messages.profile_updated'),
            'user' => $user,
        ];
    }

    public function cancelPendingEmail(User $user): void
    {
        $user->update(['pending_email' => null]);
        Log::info('Pending email cancelled', ['user_id' => $user->id]);
    }

    public function deleteUserAndTokens(User $user, $requestToken = null): void
    {
        DB::transaction(function () use ($requestToken, $user) {
            if ($requestToken) {
                $requestToken->delete();
            }

            $user->delete();
        });

        Log::info('User deactivated and deleted', ['user_id' => $user->id]);
    }
}
