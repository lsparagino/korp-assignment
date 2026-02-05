<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = \App\Models\Company::firstOrCreate(['name' => 'Acme Corp']);

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $admin->companies()->attach($company);

        $member = \App\Models\User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'role' => 'member',
        ]);
        $member->companies()->attach($company);

        $this->call([
            WalletSeeder::class,
            TransactionSeeder::class,
        ]);

        // Assign all seeded wallets to the company
        \App\Models\Wallet::query()->update(['company_id' => $company->id]);
    }
}
