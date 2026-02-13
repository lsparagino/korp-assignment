<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function dashboard(Request $request, WalletService $walletService): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->input('company_id');

        // Wallets are already authorized by 'company' middleware
        $allWallets = Wallet::scopedToUser($user, $companyId)->get();
        $walletIds = $allWallets->pluck('id');

        // Metrics via Service
        $balancesByCurrency = $walletService->getBalancesByCurrency($allWallets);
        $top3 = $walletService->getTopWallets($allWallets);
        $othersAggregated = $walletService->getOthersAggregation($allWallets);

        // Transactions via Scope
        $recentTransactions = Transaction::forWallets($walletIds)
            ->latest()
            ->limit(10)
            ->with(['fromWallet', 'toWallet'])
            ->get();

        return response()->json([
            'balances' => $balancesByCurrency,
            'top_wallets' => $top3,
            'others' => $othersAggregated,
            'transactions' => TransactionResource::collection($recentTransactions),
            'wallets' => $allWallets,
        ]);
    }
}
