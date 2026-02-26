<?php

namespace App\Policies;

use App\Enums\WalletStatus;
use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Wallet $wallet): bool
    {
        if (! $user->companies()->where('companies.id', $wallet->company_id)->exists()) {
            return false;
        }

        if ($user->canViewAllWallets()) {
            return true;
        }

        return $wallet->user_id === $user->id || $user->assignedWallets()->where('wallets.id', $wallet->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin() && ! $wallet->hasTransactions();
    }

    public function restore(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }

    public function transfer(User $user, Wallet $wallet): bool
    {
        return $wallet->status !== WalletStatus::Frozen && $this->view($user, $wallet);
    }
}
