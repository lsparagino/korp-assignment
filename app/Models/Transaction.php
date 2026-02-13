<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'type',
        'amount',
        'reference',
        'external',
    ];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * Scope a query to transactions involving any of the given wallet IDs.
     */
    public function scopeForWallets($query, $walletIds)
    {
        return $query->where(function ($q) use ($walletIds) {
            $q->whereIn('from_wallet_id', $walletIds)
                ->orWhereIn('to_wallet_id', $walletIds);
        });
    }

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'external' => 'boolean',
        ];
    }
}
