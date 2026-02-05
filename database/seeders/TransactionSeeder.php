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
            \App\Models\Transaction::factory(10)->create([
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $wallets->where('id', '!=', $wallet->id)->random()->id,
            ]);
        }
    }
}
