<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerificationService
{
    /**
     * @return array{message: string, status: int}
     */
    public function resendVerification(User $user): array
    {
        if ($user->hasVerifiedEmail() && ! $user->pending_email) {
            return ['message' => __('messages.email_already_verified'), 'status' => 400];
        }

        $user->sendEmailVerificationNotification();

        return ['message' => __('messages.verification_link_sent'), 'status' => 200];
    }

    /**
     * @return array{message: string, status: int}
     */
    public function verifyEmail(Request $request, int $id, string $hash): array
    {
        $user = User::findOrFail($id);

        if (! URL::hasValidSignature($request)) {
            return ['message' => __('messages.invalid_expired_verification_link'), 'status' => 403];
        }

        $emailToVerify = $user->pending_email ?? $user->getEmailForVerification();

        if (! hash_equals($hash, sha1($emailToVerify))) {
            return ['message' => __('messages.invalid_verification_link'), 'status' => 403];
        }

        if ($user->pending_email) {
            $user->email = $user->pending_email;
            $user->pending_email = null;
            $user->email_verified_at = now();
            $user->save();

            return ['message' => __('messages.email_updated_verified'), 'status' => 200];
        }

        if ($user->hasVerifiedEmail()) {
            return ['message' => __('messages.email_already_verified'), 'status' => 200];
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return ['message' => __('messages.email_verified'), 'status' => 200];
    }
}
