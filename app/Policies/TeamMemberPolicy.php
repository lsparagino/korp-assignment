<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class TeamMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $teamMember): bool
    {
        return $user->isAdmin() && $teamMember->role === UserRole::Member;
    }

    public function delete(User $user, User $teamMember): bool
    {
        return $user->isAdmin() && $teamMember->role === UserRole::Member;
    }
}
