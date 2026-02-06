<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 10);
        $perPage = min((int) $perPage, 500);
        $companyId = $request->input('company_id');

        // Wallet scoping using model scope
        $walletIds = \App\Models\Wallet::scopedToUser($request->user(), $companyId)->pluck('id');

        // Base query using transaction scope
        $query = \App\Models\Transaction::forWallets($walletIds);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'LIKE', '%'.$request->reference.'%');
        }

        if ($request->filled('from_wallet_id')) {
            $query->where('from_wallet_id', $request->from_wallet_id);
        }

        if ($request->filled('to_wallet_id')) {
            $query->where('to_wallet_id', $request->to_wallet_id);
        }

        $transactions = $query->with(['fromWallet', 'toWallet'])
            ->latest()
            ->paginate($perPage);

        return \App\Http\Resources\TransactionResource::collection($transactions);
    }

    public function dashboard(Request $request, \App\Services\WalletService $walletService): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $companyId = $request->input('company_id');

        // Wallets are scoped by the 'company' middleware already, but we still need the list
        $allWallets = \App\Models\Wallet::scopedToUser($user, $companyId)->get();
        $walletIds = $allWallets->pluck('id');

        // Fetch metrics using Service
        $balancesByCurrency = $walletService->getBalancesByCurrency($allWallets);
        $top3 = $walletService->getTopWallets($allWallets);
        $othersAggregated = $walletService->getOthersAggregation($allWallets);

        // Recent transactions using Scope
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
