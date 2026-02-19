<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransactionService
{
    /**
     * Get filtered and paginated transactions for the given wallet IDs.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredTransactions(Collection $walletIds, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Transaction::forWallets($walletIds);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
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

        if (($filters['from_wallet_id'] ?? null) === 'external') {
            $query->whereNull('from_wallet_id');
        } elseif (! empty($filters['from_wallet_id'])) {
            $query->where('from_wallet_id', $filters['from_wallet_id']);
        }

        if (($filters['to_wallet_id'] ?? null) === 'external') {
            $query->whereNull('to_wallet_id');
        } elseif (! empty($filters['to_wallet_id'])) {
            $query->where('to_wallet_id', $filters['to_wallet_id']);
        }

        return $query->with(['fromWallet', 'toWallet'])
            ->latest()
            ->paginate($perPage);
    }
}
