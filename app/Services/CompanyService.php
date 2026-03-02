<?php

namespace App\Services;

use App\Enums\WalletCurrency;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CompanyService
{
    public function listForUser(User $user): Collection
    {
        return $user->companies;
    }

    /**
     * @return list<string>
     */
    public function availableCurrencies(): array
    {
        return array_column(WalletCurrency::cases(), 'value');
    }
}
