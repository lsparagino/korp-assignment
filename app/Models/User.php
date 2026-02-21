<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'pending_email',
        'password',
        'role',
        'invitation_token',
        'invited_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function routeNotificationForMail(?Notification $notification): string
    {
        if ($notification instanceof VerifyEmailNotification && $this->pending_email) {
            return $this->pending_email;
        }

        return $this->email;
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function assignedWallets(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class, 'wallet_user');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->invitation_token !== null;
    }

    public function validRecoveryCode(string $code): bool
    {
        return collect($this->recoveryCodes())->first(function ($recoveryCode) use ($code) {
            return hash_equals($recoveryCode, $code);
        }) !== null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'invited_at' => 'datetime',
        ];
    }
}
