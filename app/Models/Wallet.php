<?php

namespace App\Models;

use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id');
    }

    public function getBalanceAttribute(): float
    {
        if (array_key_exists('balance', $this->attributes)) {
            return (float) ($this->attributes['balance'] ?? 0);
        }

        return (float) $this->transactions()->sum('amount');
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->withSum('transactions as balance', 'amount');
    }

    public function hasTransactions(): bool
    {
        if (array_key_exists('transactions_exists', $this->attributes)) {
            return (bool) $this->transactions_exists;
        }

        return $this->transactions()->exists();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wallet_user');
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
