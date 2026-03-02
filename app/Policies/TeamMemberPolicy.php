<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class TeamMemberPolicy
{
    public function viewAny(User $user): bool // NOSONAR - parameter required by Laravel policy contract
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $teamMember): bool
    {
        return $user->isAdmin() && $teamMember->role !== UserRole::Admin;
    }

    public function delete(User $user, User $teamMember): bool
    {
        return $user->isAdmin() && $teamMember->role !== UserRole::Admin;
    }

    public function promote(User $user): bool
    {
        return $user->isAdmin();
    }
}
