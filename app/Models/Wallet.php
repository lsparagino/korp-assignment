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
        'status',
    ];

    protected function casts(): array
    {
        return [
            'currency' => \App\Enums\WalletCurrency::class,
            'status' => \App\Enums\WalletStatus::class,
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function toTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    public function getBalanceAttribute(): float
    {
        $out = $this->fromTransactions()->sum('amount');
        $in = $this->toTransactions()->sum('amount');

        return (float) ($out - $in);
    }

    /**
     * The members assigned to this wallet.
     */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_user');
    }
}
