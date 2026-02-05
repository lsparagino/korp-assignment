<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'type',
        'amount',
        'reference',
        'external',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\TransactionType::class,
            'amount' => 'decimal:2',
            'external' => 'boolean',
        ];
    }

    public function fromWallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
}
