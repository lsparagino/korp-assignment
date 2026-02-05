<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = \App\Models\Wallet::all();

        if ($wallets->count() < 2) {
            return;
        }

        foreach ($wallets as $wallet) {
            $matchingWallets = $wallets->where('currency', $wallet->currency)
                ->where('id', '!=', $wallet->id);

            // Internal transactions
            // Only create internal transactions if there are matching wallets of the same currency
            if ($matchingWallets->isNotEmpty()) {
                \App\Models\Transaction::factory(8)->create([
                    'from_wallet_id' => $wallet->id,
                    'to_wallet_id' => $matchingWallets->random()->id,
                    'external' => false,
                ]);
            }


            // External Outbound
            \App\Models\Transaction::factory(1)->create([
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => null,
                'type' => \App\Enums\TransactionType::Debit,
                'external' => true,
            ]);

            // External Inbound
            \App\Models\Transaction::factory(1)->create([
                'from_wallet_id' => null,
                'to_wallet_id' => $wallet->id,
                'type' => \App\Enums\TransactionType::Credit,
                'external' => true,
            ]);
        }
    }
}
