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
    public function verifyEmail(Request $request, int $id, string $hash): array
    {
        $user = User::findOrFail($id);

        if (! URL::hasValidSignature($request)) {
            return ['message' => 'Invalid or expired verification link', 'status' => 403];
        }

        $emailToVerify = $user->pending_email ?? $user->getEmailForVerification();

        if (! hash_equals($hash, sha1($emailToVerify))) {
            return ['message' => 'Invalid verification link', 'status' => 403];
        }

        if ($user->pending_email) {
            $user->email = $user->pending_email;
            $user->pending_email = null;
            $user->email_verified_at = now();
            $user->save();

            return ['message' => 'Email address updated and verified successfully', 'status' => 200];
        }

        if ($user->hasVerifiedEmail()) {
            return ['message' => 'Email already verified', 'status' => 200];
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return ['message' => 'Email verified successfully', 'status' => 200];
    }
}
