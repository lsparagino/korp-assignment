<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('exclude_cancelled', function (Builder $query) {
            $query->where('status', '!=', TransactionStatus::Cancelled);
        });
    }

    protected $fillable = [
        'group_id',
        'wallet_id',
        'counterpart_wallet_id',
        'type',
        'amount',
        'external',
        'reference',
        'status',
        'source_currency',
        'destination_currency',
        'exchange_rate',
        'initiator_user_id',
        'reviewer_user_id',
        'reject_reason',
        'external_address',
        'external_name',
        'notes',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function counterpartWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'counterpart_wallet_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function scopeForWallets(Builder $query, Collection $walletIds): Builder
    {
        return $query->whereIn('wallet_id', $walletIds);
    }

    public function scopeRecentForWallets(Builder $query, Collection $walletIds, int $limit = 10): Builder
    {
        return $query->forWallets($walletIds)
            ->with(['wallet', 'counterpartWallet'])
            ->latest()
            ->limit($limit);
    }

    /**
     * De-duplicate internal transfers where the user owns both wallets.
     *
     * Keeps all entries where the counterpart is NOT one of the user's wallets,
     * plus only the debit entry for transfers between the user's own wallets.
     */
    public function scopeDeduplicatedForWallets(Builder $query, Collection $walletIds): Builder
    {
        return $query->forWallets($walletIds)
            ->where(function (Builder $q) use ($walletIds) {
                $q->whereNull('counterpart_wallet_id')
                    ->orWhereNotIn('counterpart_wallet_id', $walletIds)
                    ->orWhere(function (Builder $inner) use ($walletIds) {
                        $inner->whereIn('counterpart_wallet_id', $walletIds)
                            ->where('type', TransactionType::Debit);
                    });
            });
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'external' => 'boolean',
        ];
    }
}
