<?php

namespace App\Models;

use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->toTransactions()->sum('amount') + $this->fromTransactions()->sum('amount'));
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query
            ->withSum('toTransactions as balance_in', 'amount')
            ->withSum('fromTransactions as balance_out', 'amount');
    }

    public function fromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function toTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_user');
    }

    public function hasTransactions(): bool
    {
        if (array_key_exists('from_transactions_exists', $this->attributes) && array_key_exists('to_transactions_exists', $this->attributes)) {
            return $this->from_transactions_exists || $this->to_transactions_exists;
        }

        return $this->fromTransactions()->exists() || $this->toTransactions()->exists();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeScopedToUser(Builder $query, User $user, ?int $companyId = null): Builder
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

    public function toggleFreeze(): void
    {
        $this->update([
            'status' => $this->status === WalletStatus::Active
                ? WalletStatus::Frozen
                : WalletStatus::Active,
        ]);
    }

    protected function casts(): array
    {
        return [
            'currency' => WalletCurrency::class,
            'status' => WalletStatus::class,
        ];
    }
}
