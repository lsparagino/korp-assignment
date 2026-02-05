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
        \App\Models\Wallet::all()->each(function (\App\Models\Wallet $wallet) {
            \App\Models\Transaction::factory(10)->create([
                'from_wallet_id' => $wallet->id,
            ]);
        });
    }
}
