<?php

namespace App\Services;

use App\Enums\WalletStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function create(User $user, array $data): Wallet
    {
        $wallet = $user->wallets()->create([
            'name' => $data['name'],
            'currency' => $data['currency'],
            'status' => WalletStatus::Active,
            'company_id' => $data['company_id'],
        ]);

        Log::info('Wallet created', ['wallet_id' => $wallet->id, 'user_id' => $user->id]);

        return $wallet;
    }

    public function getBalancesByCurrency(Collection $wallets): Collection
    {
        return $wallets->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group) => $group->sum(fn ($w) => $w->balance))
            ->map(fn ($total, $currency) => ['currency' => $currency, 'amount' => $total])
            ->values();
    }

    public function getTopWallets(Collection $wallets, int $limit = 3): Collection
    {
        return $wallets->sortByDesc(fn ($w) => $w->balance)
            ->take($limit)
            ->map(fn ($w) => [
                'name' => $w->name,
                'balance' => $w->balance,
                'currency' => $w->currency->value,
            ])->values();
    }

    public function getOthersAggregation(Collection $wallets, int $topLimit = 3): Collection
    {
        $others = $wallets->sortByDesc(fn ($w) => $w->balance)->slice($topLimit);

        if ($others->isEmpty()) {
            return collect();
        }

        return $others->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group, $currency) => [
                'currency' => $currency,
                'amount' => $group->sum(fn ($w) => $w->balance),
            ])->values();
    }
}
