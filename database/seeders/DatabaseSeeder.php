<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'Acme Corp']);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $admin->companies()->attach($company);
        UserSetting::create(['user_id' => $admin->id]);

        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'role' => 'member',
        ]);
        $member->companies()->attach($company);
        UserSetting::create(['user_id' => $member->id]);

        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);
        $manager->companies()->attach($company);
        UserSetting::create(['user_id' => $manager->id]);

        $this->call([
            WalletSeeder::class,
            TransactionSeeder::class,
        ]);

        // Assign all seeded wallets to the company
        Wallet::query()->update(['company_id' => $company->id]);
    }
}
