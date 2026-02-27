<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Http\Resources\TransactionResource;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(
        private WalletService $walletService,
        private TransactionService $transactionService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?int $companyId): array
    {
        $allWallets = Wallet::scopedToUser($user, $companyId)
            ->withBalance()
            ->withExists('transactions')
            ->get();

        $balancesByCurrency = $this->walletService->getBalancesByCurrency($allWallets);
        $top3 = $this->walletService->getTopWallets($allWallets);
        $othersAggregated = $this->walletService->getOthersAggregation($allWallets);

        $recentTransactions = $this->transactionService->getFilteredTransactions($user, $companyId, [], 10);

        Log::info('Dashboard data accessed', ['user_id' => $user->id, 'company_id' => $companyId]);

        $data = [
            'balances' => $balancesByCurrency,
            'top_wallets' => $top3,
            'others' => $othersAggregated,
            'transactions' => TransactionResource::collection($recentTransactions),
            'wallets' => $allWallets,
        ];

        if ($user->role === UserRole::Admin && $companyId) {
            $data['missing_thresholds'] = CompanySetting::where('company_id', $companyId)->count() === 0;
        }

        if (in_array($user->role, [UserRole::Admin, UserRole::Manager]) && $companyId) {
            $companyWalletIds = Wallet::where('company_id', $companyId)->pluck('id');

            $pendingTransactions = Transaction::deduplicatedForWallets($companyWalletIds)
                ->where('status', TransactionStatus::PendingApproval)
                ->with(['wallet', 'counterpartWallet', 'initiator'])
                ->latest()
                ->limit(5)
                ->get();

            $data['pending_transactions'] = TransactionResource::collection($pendingTransactions);
        }

        return $data;
    }
}
