<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    /** @use HasFactory<\Database\Factories\WalletFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'status',
        'company_id',
        'address',
    ];

    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet) {
            if (empty($wallet->address)) {
                $wallet->address = 'bc1q'.Str::lower(Str::random(36));
            }
        });
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->toTransactions()->sum('amount') + $this->fromTransactions()->sum('amount'));
    }

    /**
     * Scope to add pre-computed balance sums for eager loading.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithBalance($query)
    {
        return $query
            ->withSum('toTransactions as balance_in', 'amount')
            ->withSum('fromTransactions as balance_out', 'amount');
    }

    public function fromTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function toTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    /**
     * The members assigned to this wallet.
     */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_user');
    }

    public function hasTransactions(): bool
    {
        return $this->fromTransactions()->exists() || $this->toTransactions()->exists();
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to wallets accessible to the given user in a specific company.
     */
    public function scopeScopedToUser($query, User $user, $companyId = null)
    {
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('members', fn ($mq) => $mq->where('users.id', $user->id));
        });
    }

    protected function casts(): array
    {
        return [
            'currency' => \App\Enums\WalletCurrency::class,
            'status' => \App\Enums\WalletStatus::class,
        ];
    }
}
