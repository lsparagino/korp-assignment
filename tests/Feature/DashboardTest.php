<?php

use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

const DASHBOARD_ENDPOINT = '/api/v0/dashboard';

test('guests are denied access to dashboard', function () {
    $response = $this->getJson(DASHBOARD_ENDPOINT);
    $response->assertUnauthorized();
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user, 'sanctum');

    $response = $this->getJson(DASHBOARD_ENDPOINT."?company_id={$company->id}");
    $response->assertOk()
        ->assertJsonStructure([
            'balances',
            'top_wallets',
            'others',
            'transactions',
            'wallets',
        ]);
});

test('top wallets are sorted by balance descending', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $walletA = Wallet::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'currency' => 'USD', 'name' => 'Low Wallet']);
    $walletB = Wallet::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'currency' => 'USD', 'name' => 'High Wallet']);
    $walletC = Wallet::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'currency' => 'USD', 'name' => 'Mid Wallet']);

    // Credit wallet A with 100
    Transaction::factory()->create(['wallet_id' => $walletA->id, 'amount' => 100, 'type' => TransactionType::Credit, 'external' => true]);
    // Credit wallet B with 500
    Transaction::factory()->create(['wallet_id' => $walletB->id, 'amount' => 500, 'type' => TransactionType::Credit, 'external' => true]);
    // Credit wallet C with 250
    Transaction::factory()->create(['wallet_id' => $walletC->id, 'amount' => 250, 'type' => TransactionType::Credit, 'external' => true]);

    $this->actingAs($user, 'sanctum');

    $response = $this->getJson("/api/v0/dashboard?company_id={$company->id}");
    $response->assertOk();

    $topWallets = $response->json('top_wallets');
    expect($topWallets)->toHaveCount(3);
    expect($topWallets[0]['name'])->toBe('High Wallet');
    expect($topWallets[1]['name'])->toBe('Mid Wallet');
    expect($topWallets[2]['name'])->toBe('Low Wallet');
});

test('wallet balance uses eager-loaded values when available', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'currency' => 'USD']);

    Transaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => 300, 'type' => TransactionType::Credit, 'external' => true]);
    Transaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => -50, 'type' => TransactionType::Debit, 'external' => true]);

    // Without eager loading (accessor queries DB directly)
    $accessorBalance = $wallet->fresh()->balance;

    // With eager loading (via scopeWithBalance)
    $eagerWallet = Wallet::withBalance()->find($wallet->id);
    $eagerBalance = $eagerWallet->balance;

    expect($eagerBalance)->toBe($accessorBalance);
    expect($eagerBalance)->toBe(250.0);
});

test('dashboard returns pending_transactions for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $company = Company::factory()->create();
    $admin->companies()->attach($company);

    $member = User::factory()->create(['role' => 'member']);
    $member->companies()->attach($company);

    $wallet = Wallet::factory()->create(['user_id' => $member->id, 'company_id' => $company->id, 'currency' => 'USD']);
    Transaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => 5000, 'type' => TransactionType::Credit, 'external' => true]);

    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => TransactionType::Debit,
        'amount' => -1000,
        'status' => 'pending_approval',
        'external' => true,
        'initiator_user_id' => $member->id,
    ]);

    $this->actingAs($admin, 'sanctum');

    $response = $this->getJson("/api/v0/dashboard?company_id={$company->id}");
    $response->assertOk()
        ->assertJsonStructure(['pending_transactions'])
        ->assertJsonCount(1, 'pending_transactions');
});

test('dashboard does not return pending_transactions for member', function () {
    $member = User::factory()->create(['role' => 'member']);
    $company = Company::factory()->create();
    $member->companies()->attach($company);

    $wallet = Wallet::factory()->create(['user_id' => $member->id, 'company_id' => $company->id, 'currency' => 'USD']);
    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => TransactionType::Debit,
        'amount' => -1000,
        'status' => 'pending_approval',
        'external' => true,
    ]);

    $this->actingAs($member, 'sanctum');

    $response = $this->getJson("/api/v0/dashboard?company_id={$company->id}");
    $response->assertOk()
        ->assertJsonMissing(['pending_transactions']);
});
