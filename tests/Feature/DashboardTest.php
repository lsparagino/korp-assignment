<?php

use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

test('guests are denied access to dashboard', function () {
    $response = $this->getJson('/api/v0/dashboard');
    $response->assertUnauthorized();
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user, 'sanctum');

    $response = $this->getJson("/api/v0/dashboard?company_id={$company->id}");
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
    Transaction::factory()->create(['to_wallet_id' => $walletA->id, 'from_wallet_id' => null, 'amount' => 100, 'type' => TransactionType::Credit]);
    // Credit wallet B with 500
    Transaction::factory()->create(['to_wallet_id' => $walletB->id, 'from_wallet_id' => null, 'amount' => 500, 'type' => TransactionType::Credit]);
    // Credit wallet C with 250
    Transaction::factory()->create(['to_wallet_id' => $walletC->id, 'from_wallet_id' => null, 'amount' => 250, 'type' => TransactionType::Credit]);

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

    Transaction::factory()->create(['to_wallet_id' => $wallet->id, 'from_wallet_id' => null, 'amount' => 300, 'type' => TransactionType::Credit]);
    Transaction::factory()->create(['from_wallet_id' => $wallet->id, 'to_wallet_id' => null, 'amount' => -50, 'type' => TransactionType::Debit]);

    // Without eager loading (accessor queries DB directly)
    $accessorBalance = $wallet->fresh()->balance;

    // With eager loading (via scopeWithBalance)
    $eagerWallet = Wallet::withBalance()->find($wallet->id);
    $eagerBalance = $eagerWallet->balance;

    expect($eagerBalance)->toBe($accessorBalance);
    expect($eagerBalance)->toBe(250.0);
});

