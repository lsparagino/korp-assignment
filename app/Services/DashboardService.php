<?php

namespace App\Services;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?int $companyId): array
    {
        $allWallets = Wallet::scopedToUser($user, $companyId)
            ->withBalance()
            ->withExists(['fromTransactions', 'toTransactions'])
            ->get();

        $walletIds = $allWallets->pluck('id');

        $balancesByCurrency = $this->walletService->getBalancesByCurrency($allWallets);
        $top3 = $this->walletService->getTopWallets($allWallets);
        $othersAggregated = $this->walletService->getOthersAggregation($allWallets);

        $recentTransactions = Transaction::forWallets($walletIds)
            ->latest()
            ->limit(10)
            ->with(['fromWallet', 'toWallet'])
            ->get();

        Log::info('Dashboard data accessed', ['user_id' => $user->id, 'company_id' => $companyId]);

        return [
            'balances' => $balancesByCurrency,
            'top_wallets' => $top3,
            'others' => $othersAggregated,
            'transactions' => TransactionResource::collection($recentTransactions),
            'wallets' => $allWallets,
        ];
    }
}
