<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first() ?: \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $names = ['Savings', 'Offshore', 'Business', 'Person A', 'Person B'];

        foreach ($names as $name) {
            \App\Models\Wallet::factory()->create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }
    }
}
