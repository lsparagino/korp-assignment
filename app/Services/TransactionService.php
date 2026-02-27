<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get filtered and paginated transactions for the given user.
     *
     * Returns de-duplicated results: when the user owns both wallets
     * in an internal transfer, only the debit entry is returned.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredTransactions(User $user, ?int $companyId, array $filters, int $perPage): LengthAwarePaginator
    {
        $walletIds = Wallet::scopedToUser($user, $companyId)->pluck('id');

        $query = Transaction::deduplicatedForWallets($walletIds);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        if (! empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (! empty($filters['reference'])) {
            $query->where('reference', 'LIKE', '%'.$filters['reference'].'%');
        }

        if (($filters['wallet_id'] ?? null) === 'external') {
            $query->whereNull('counterpart_wallet_id');
        } elseif (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        if (($filters['counterpart_wallet_id'] ?? null) === 'external') {
            $query->whereNull('counterpart_wallet_id');
        } elseif (! empty($filters['counterpart_wallet_id'])) {
            $query->where('counterpart_wallet_id', $filters['counterpart_wallet_id']);
        }

        return $query->with(['wallet', 'counterpartWallet', 'initiator', 'reviewer'])
            ->latest()
            ->paginate($perPage);
    }
}
