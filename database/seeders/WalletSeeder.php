<?php

namespace Database\Seeders;

use App\Enums\WalletCurrency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Wallets to seed, each with a specific name and currency.
     *
     * @var array<string, WalletCurrency>
     */
    private const WALLETS = [
        'Savings' => WalletCurrency::USD,
        'Business' => WalletCurrency::USD,
        'Offshore' => WalletCurrency::EUR,
        'Person A' => WalletCurrency::EUR,
        'Person B' => WalletCurrency::GBP,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?: User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach (self::WALLETS as $name => $currency) {
            Wallet::factory()->create([
                'user_id' => $user->id,
                'name' => $name,
                'currency' => $currency,
            ]);
        }
    }
}
