<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\TeamMemberInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamMemberService
{
    /**
     * Invite a new team member.
     *
     * Creates a user, attaches to company, syncs wallets, and sends invitation email.
     */
    public function invite(string $name, string $email, int $companyId, array $wallets = []): User
    {
        $user = DB::transaction(function () use ($name, $email, $companyId, $wallets) {
            $token = Str::random(64);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Member,
                'invitation_token' => $token,
                'invited_at' => now(),
            ]);

            $user->companies()->attach($companyId);

            if (! empty($wallets)) {
                $user->assignedWallets()->sync($wallets);
            }

            return $user;
        });

        Mail::to($user->email)->send(new TeamMemberInvitation($user));

        Log::info('Team member invited', ['user_id' => $user->id, 'email' => $email, 'company_id' => $companyId]);

        return $user;
    }

    /**
     * Update an existing team member.
     */
    public function update(User $member, string $name, string $email, array $wallets = []): User
    {
        $member->update([
            'name' => $name,
            'email' => $email,
        ]);

        if (! empty($wallets)) {
            $member->assignedWallets()->sync($wallets);
        }

        return $member;
    }

    /**
     * Delete a team member.
     */
    public function delete(User $member): void
    {
        $member->delete();

        Log::info('Team member deleted', ['user_id' => $member->id]);
    }
}
