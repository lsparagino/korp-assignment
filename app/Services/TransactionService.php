<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            $tz = $filters['tz'] ?? 'UTC';
            $from = Carbon::parse($filters['date_from'], $tz)->startOfDay()->utc();
            $query->where('created_at', '>=', $from);
        }

        if (! empty($filters['date_to'])) {
            $tz = $filters['tz'] ?? 'UTC';
            $to = Carbon::parse($filters['date_to'], $tz)->endOfDay()->utc();
            $query->where('created_at', '<=', $to);
        }

        if (! empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        $this->applyReferenceFilter($query, $filters);
        $this->applyFromWalletFilter($query, $filters);
        $this->applyToWalletFilter($query, $filters);

        if (! empty($filters['initiator_user_id'])) {
            $query->where('initiator_user_id', $filters['initiator_user_id']);
        }

        $this->applyHasWalletFilter($query, $filters);

        return $query->with(['wallet', 'counterpartWallet', 'initiator', 'reviewer'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyReferenceFilter(Builder $query, array $filters): void
    {
        if (empty($filters['reference'])) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $query->where('reference', 'LIKE', '%'.$filters['reference'].'%');
        } else {
            $query->whereFullText('reference', $filters['reference']);
        }
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFromWalletFilter(Builder $query, array $filters): void
    {
        if (($filters['from_wallet_id'] ?? null) === 'external') {
            $query->where('type', TransactionType::Credit)->where('external', true);
        } elseif (! empty($filters['from_wallet_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('type', TransactionType::Debit)->where('wallet_id', $filters['from_wallet_id']);
                })->orWhere(function ($sub) use ($filters) {
                    $sub->where('type', TransactionType::Credit)->where('counterpart_wallet_id', $filters['from_wallet_id']);
                });
            });
        }
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyToWalletFilter(Builder $query, array $filters): void
    {
        if (($filters['to_wallet_id'] ?? null) === 'external') {
            $query->where('type', TransactionType::Debit)->where('external', true);
        } elseif (! empty($filters['to_wallet_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('type', TransactionType::Debit)->where('counterpart_wallet_id', $filters['to_wallet_id']);
                })->orWhere(function ($sub) use ($filters) {
                    $sub->where('type', TransactionType::Credit)->where('wallet_id', $filters['to_wallet_id']);
                });
            });
        }
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyHasWalletFilter(Builder $query, array $filters): void
    {
        if (($filters['has_wallet_id'] ?? null) === 'external') {
            $query->where('external', true);
        } elseif (! empty($filters['has_wallet_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('wallet_id', $filters['has_wallet_id'])
                    ->orWhere('counterpart_wallet_id', $filters['has_wallet_id']);
            });
        }
    }
}
