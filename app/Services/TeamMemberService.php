<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
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
    public function __construct(private AuditService $auditService) {}

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

    public function find(User $member): User
    {
        return $member->loadCount('assignedWallets')->load('assignedWallets');
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

        $this->auditService->log(
            AuditCategory::Team,
            AuditSeverity::Medium,
            'team.member_invited',
            __('messages.audit.team_member_invited'),
            ['metadata' => ['invited_user_id' => $user->id, 'member_name' => $name, 'member_email' => $email]],
        );

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

        $this->auditService->log(
            AuditCategory::Team,
            AuditSeverity::Normal,
            'team.member_updated',
            __('messages.audit.team_member_updated'),
            ['metadata' => ['member_id' => $member->id, 'member_name' => $member->name, 'member_email' => $member->email]],
        );

        return $member;
    }

    public function promote(User $member, UserRole $role): void
    {
        $previousRole = $member->role->value;
        $member->update(['role' => $role]);

        Log::info('Team member role updated', ['user_id' => $member->id, 'role' => $role->value]);

        $this->auditService->log(
            AuditCategory::Team,
            AuditSeverity::High,
            'team.member_promoted',
            __('messages.audit.team_member_promoted'),
            ['metadata' => [
                'member_id' => $member->id,
                'member_name' => $member->name,
                'changes' => [
                    'role' => [
                        'from' => $previousRole,
                        'to' => $role->value,
                    ],
                ],
            ]],
        );
    }

    public function delete(User $member): void
    {
        $memberId = $member->id;
        $memberName = $member->name;

        $member->delete();

        Log::info('Team member deleted', ['user_id' => $memberId]);

        $this->auditService->log(
            AuditCategory::Team,
            AuditSeverity::High,
            'team.member_removed',
            __('messages.audit.team_member_removed'),
            ['metadata' => ['member_id' => $memberId, 'member_name' => $memberName]],
        );
    }
}
