<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        if ($wallets->count() < 2) {
            return;
        }

        foreach ($wallets as $wallet) {
            $matchingWallets = $wallets->where('currency', $wallet->currency)
                ->where('id', '!=', $wallet->id);

            // Internal transactions
            // Only create internal transactions if there are matching wallets of the same currency
            if ($matchingWallets->isNotEmpty()) {
                Transaction::factory(8)->create([
                    'from_wallet_id' => $wallet->id,
                    'to_wallet_id' => $matchingWallets->random()->id,
                    'external' => false,
                ]);
            }


            // External Outbound
            Transaction::factory(1)->create([
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => null,
                'type' => TransactionType::Debit,
                'external' => true,
            ]);

            // External Inbound
            Transaction::factory(1)->create([
                'from_wallet_id' => null,
                'to_wallet_id' => $wallet->id,
                'type' => TransactionType::Credit,
                'external' => true,
            ]);
        }
    }
}
