<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InvitationService
{
    /**
     * @return array{message: string, user: User, access_token: string, token_type: string}
     */
    public function acceptInvitation(string $token, string $password): array
    {
        $user = User::findByInvitationTokenOrFail($token);

        DB::transaction(function () use ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
                'invitation_token' => null,
                'email_verified_at' => now(),
            ]);
        });

        return [
            'message' => __('messages.invitation_accepted'),
            ...$user->createAuthTokenResponse(),
        ];
    }
}
