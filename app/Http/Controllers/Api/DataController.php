<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataController extends Controller
{

    public function transactions()
    {
        return response()->json([
            'company' => 'Acme Corp',
            'transactions' => [
                ['id' => 1, 'date' => '12/10/2022', 'wallet' => 'Main Wallet', 'type' => 'Debit', 'amount' => -500.00, 'currency' => 'USD', 'reference' => 'Invoice #123'],
                ['id' => 2, 'date' => '12/09/2022', 'wallet' => 'EUR Wallet', 'type' => 'Credit', 'amount' => 1000.00, 'currency' => 'EUR', 'reference' => 'Client Payment'],
                ['id' => 3, 'date' => '12/08/2022', 'wallet' => 'Marketing Wallet', 'type' => 'Debit', 'amount' => -200.00, 'currency' => 'USD', 'reference' => 'Advertising'],
                ['id' => 4, 'date' => '12/07/2022', 'wallet' => 'Main Wallet', 'type' => 'Credit', 'amount' => 2500.00, 'currency' => 'EUR', 'reference' => 'Transfer'],
            ],
        ]);
    }

    public function team()
    {
        return response()->json([
            'company' => 'Acme Corp',
            'members' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'admin@acme.com', 'role' => 'Admin', 'wallet_access' => 'All'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@acme.com', 'role' => 'Member', 'wallet_access' => 'Marketing Wallet'],
            ],
        ]);
    }

    public function dashboard(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // 1. Determine authorized wallets
        if ($user->isAdmin()) {
            $walletsQuery = $user->wallets();
        } else {
            $walletsQuery = $user->assignedWallets();
        }

        $allWallets = $walletsQuery->get();
        $walletIds = $allWallets->pluck('id');

        // 2. Calculate Total Balances by currency
        $balancesByCurrency = $allWallets->groupBy(fn($w) => $w->currency->value)
            ->map(fn($group) => $group->sum(fn($w) => $w->balance))
            ->map(fn($total, $currency) => ['currency' => $currency, 'amount' => $total])
            ->values();

        // 3. Best performing wallets (Top 3)
        $sortedWallets = $allWallets->sortByDesc(fn($w) => $w->balance);
        $top3 = $sortedWallets->take(3)->map(fn($w) => [
            'name' => $w->name,
            'balance' => $w->balance,
            'currency' => $w->currency->value,
        ])->values();

        // 4. Other Wallets Badge aggregation
        $others = $sortedWallets->slice(3);
        $othersAggregated = [];
        if ($others->count() > 0) {
           $othersAggregated = $others->groupBy(fn($w) => $w->currency->value)
                ->map(fn($group, $currency) => [
                    'currency' => $currency,
                    'amount' => $group->sum(fn($w) => $w->balance)
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
            ->get()
            ->map(function ($t) use ($walletIds) {
                // Determine if this is a Debit or Credit relative to the user's view
                // Actually, let's just use the absolute amount and the type from the transaction
                // But for the dashboard, "To/From" should be clear.
                return [
                    'id' => $t->id,
                    'date' => $t->created_at->format('Y-m-d'),
                    'from' => $t->fromWallet->name,
                    'to' => $t->toWallet->name,
                    'amount' => (float) $t->amount,
                    'currency' => $t->fromWallet->currency->value,
                    'type' => $t->type->value,
                ];
            });

        return response()->json([
            'balances' => $balancesByCurrency,
            'top_wallets' => $top3,
            'others' => $othersAggregated,
            'transactions' => $recentTransactions,
        ]);
    }
}
