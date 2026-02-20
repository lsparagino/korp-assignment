<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail() && ! $request->user()->pending_email) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent']);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! URL::hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid or expired verification link'], 403);
        }

        $emailToVerify = $user->pending_email ?? $user->getEmailForVerification();

        if (! hash_equals($hash, sha1($emailToVerify))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if ($user->pending_email) {
            $user->email = $user->pending_email;
            $user->pending_email = null;
            $user->email_verified_at = now();
            $user->save();

            return response()->json(['message' => 'Email address updated and verified successfully']);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully']);
    }
}
