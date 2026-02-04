<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    /** @use HasFactory<\Database\Factories\WalletFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'currency' => \App\Enums\WalletCurrency::class,
            'status' => \App\Enums\WalletStatus::class,
            'balance' => 'decimal:2',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
