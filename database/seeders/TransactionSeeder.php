<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    private const INITIAL_DEPOSIT = 20000;

    /**
     * External bank details per currency.
     *
     * @var array<string, array{address: string, name: string}>
     */
    private const EXTERNAL_BANKS = [
        'USD' => [
            'address' => 'DBS005-231289-1',
            'name' => 'DBS Bank Ltd',
        ],
        'EUR' => [
            'address' => 'IT08CRACIT33441953155986496',
            'name' => 'Unicredit S.p.A.',
        ],
        'GBP' => [
            'address' => 'GB08MOLUGB422784518172',
            'name' => 'Barclays Bank UK',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Wallet::all()->each(function (Wallet $wallet) {
            $currency = $wallet->currency instanceof WalletCurrency
                ? $wallet->currency->value
                : (string) $wallet->currency;

            $bank = self::EXTERNAL_BANKS[$currency] ?? self::EXTERNAL_BANKS['USD'];

            Transaction::factory()->create([
                'wallet_id' => $wallet->id,
                'counterpart_wallet_id' => null,
                'type' => TransactionType::Credit,
                'amount' => self::INITIAL_DEPOSIT,
                'external' => true,
                'external_address' => $bank['address'],
                'external_name' => $bank['name'],
                'reference' => 'Deposit',
                'source_currency' => $currency,
                'destination_currency' => $currency,
            ]);
        });
    }
}
