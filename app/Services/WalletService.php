<?php

namespace App\Services;

use Illuminate\Support\Collection;

class WalletService
{
    /**
     * Get balances aggregated by currency for the given wallets.
     */
    public function getBalancesByCurrency(Collection $wallets): Collection
    {
        return $wallets->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group) => $group->sum(fn ($w) => $w->balance))
            ->map(fn ($total, $currency) => ['currency' => $currency, 'amount' => $total])
            ->values();
    }

    /**
     * Get top-performing wallets by balance.
     */
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

    /**
     * Get aggregated balances for wallets not in the top N.
     */
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
