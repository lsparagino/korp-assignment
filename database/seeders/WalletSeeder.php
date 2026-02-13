<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?: User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $names = ['Savings', 'Offshore', 'Business', 'Person A', 'Person B'];

        foreach ($names as $name) {
            Wallet::factory()->create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }
    }
}
