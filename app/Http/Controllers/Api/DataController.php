<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function dashboard(Request $request, \App\Services\WalletService $walletService): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $companyId = $request->input('company_id');

        // Wallets are already authorized by 'company' middleware
        $allWallets = \App\Models\Wallet::scopedToUser($user, $companyId)->get();
        $walletIds = $allWallets->pluck('id');

        // Metrics via Service
        $balancesByCurrency = $walletService->getBalancesByCurrency($allWallets);
        $top3 = $walletService->getTopWallets($allWallets);
        $othersAggregated = $walletService->getOthersAggregation($allWallets);

        // Transactions via Scope
        $recentTransactions = \App\Models\Transaction::forWallets($walletIds)
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
