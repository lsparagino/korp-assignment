<?php

namespace App\Models;

use Database\Factories\AddressBookEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressBookEntry extends Model
{
    /** @use HasFactory<AddressBookEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'user_id',
        'company_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForUserInCompany(Builder $query, int $userId, int $companyId): Builder
    {
        return $query->where('user_id', $userId)->where('company_id', $companyId);
    }
}
