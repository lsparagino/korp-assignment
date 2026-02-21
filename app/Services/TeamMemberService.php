<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\TeamMemberInvitation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamMemberService
{
    public function list(int $companyId): LengthAwarePaginator
    {
        return User::forCompany($companyId)
            ->with(['assignedWallets' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->withCount(['assignedWallets' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->orderBy('name')
            ->paginate(10);
    }

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

    public function update(User $member, string $name, string $email, array $wallets = []): User
    {
        DB::transaction(function () use ($member, $name, $email, $wallets) {
            $member->update([
                'name' => $name,
                'email' => $email,
            ]);

            $member->assignedWallets()->sync($wallets);
        });

        return $member;
    }

    public function delete(User $member): void
    {
        $member->delete();

        Log::info('Team member deleted', ['user_id' => $member->id]);
    }
}
