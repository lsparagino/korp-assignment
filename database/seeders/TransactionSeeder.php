<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    private const INITIAL_DEPOSIT = 20000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Wallet::all()->each(function (Wallet $wallet) {
            Transaction::factory()->create([
                'wallet_id' => $wallet->id,
                'counterpart_wallet_id' => null,
                'type' => TransactionType::Credit,
                'amount' => self::INITIAL_DEPOSIT,
                'external' => true,
                'external_address' => 'DBS005-231289-1',
                'external_name' => 'DBS Bank Ltd',
                'reference' => 'Deposit',
            ]);
        });
    }
}
