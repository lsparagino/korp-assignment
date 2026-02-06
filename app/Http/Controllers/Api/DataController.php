<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataController extends Controller
{
        public function dashboard(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $companyId = $request->input('company_id');

        if (! $companyId) {
            return response()->json([
                'balances' => [],
                'top_wallets' => [],
                'others' => [],
                'transactions' => [],
            ]);
        }

        // Ensure user belongs to this company
        if (! $user->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'Unauthorized access to company.');
        }

        // 1. Determine authorized wallets
        if ($user->isAdmin()) {
            $walletsQuery = \App\Models\Wallet::where('company_id', $companyId);
        } else {
            $walletsQuery = $user->assignedWallets()->where('company_id', $companyId);
        }

        $allWallets = $walletsQuery->get();
        $walletIds = $allWallets->pluck('id');

        // 2. Calculate Total Balances by currency
        $balancesByCurrency = $allWallets->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group) => $group->sum(fn ($w) => $w->balance))
            ->map(fn ($total, $currency) => ['currency' => $currency, 'amount' => $total])
            ->values();

        // 3. Best performing wallets (Top 3)
        $sortedWallets = $allWallets->sortByDesc(fn ($w) => $w->balance);
        $top3 = $sortedWallets->take(3)->map(fn ($w) => [
            'name' => $w->name,
            'balance' => $w->balance,
            'currency' => $w->currency->value,
        ])->values();

        // 4. Other Wallets Badge aggregation
        $others = $sortedWallets->slice(3);
        $othersAggregated = [];
        if ($others->count() > 0) {
            $othersAggregated = $others->groupBy(fn ($w) => $w->currency->value)
                ->map(fn ($group, $currency) => [
                    'currency' => $currency,
                    'amount' => $group->sum(fn ($w) => $w->balance),
                ])->values();
        }

        // 5. 10 most recent transactions
        $recentTransactions = \App\Models\Transaction::query()
            ->where(function ($q) use ($walletIds) {
                $q->whereIn('from_wallet_id', $walletIds)
                    ->orWhereIn('to_wallet_id', $walletIds);
            })
            ->latest()
            ->limit(10)
            ->with(['fromWallet', 'toWallet'])
            ->get();

        return response()->json([
            'balances' => $balancesByCurrency,
            'top_wallets' => $top3,
            'others' => $othersAggregated,
            'transactions' => \App\Http\Resources\TransactionResource::collection($recentTransactions),
            'wallets' => $allWallets,
        ]);
    }
}
