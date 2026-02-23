<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->user->companies()->attach($this->company);
});

test('wallet with no transactions has zero balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    expect($wallet->balance)->toBe(0.0);
    expect(Wallet::withBalance()->find($wallet->id)->balance)->toBe(0.0);
});

test('single credit increases balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 100,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    expect($wallet->fresh()->balance)->toBe(100.0);
});

test('single debit decreases balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    // Fund the wallet first
    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 200,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    // Debit
    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -50,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    expect($wallet->fresh()->balance)->toBe(150.0);
});

test('multiple credits and debits produce correct balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    // +100, +1000, -10, -100.50 = 989.50
    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 100,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 1000,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -10,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -100.50,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    expect($wallet->fresh()->balance)->toBe(989.50);
});

test('internal transfer correctly affects both wallets', function () {
    $sender = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
    ]);

    $receiver = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
    ]);

    // Fund the sender
    Transaction::factory()->create([
        'to_wallet_id' => $sender->id,
        'from_wallet_id' => null,
        'amount' => 1000,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    // Internal transfer: debit from sender (only from_wallet_id)
    Transaction::factory()->create([
        'from_wallet_id' => $sender->id,
        'to_wallet_id' => null,
        'amount' => -100,
        'type' => TransactionType::Debit,
        'external' => false,
    ]);

    // Internal transfer: credit to receiver (only to_wallet_id)
    Transaction::factory()->create([
        'from_wallet_id' => null,
        'to_wallet_id' => $receiver->id,
        'amount' => 100,
        'type' => TransactionType::Credit,
        'external' => false,
    ]);

    expect($sender->fresh()->balance)->toBe(900.0);
    expect($receiver->fresh()->balance)->toBe(100.0);
});

test('eager-loaded balance matches accessor balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 1000,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -100.50,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    $accessorBalance = $wallet->fresh()->balance;
    $eagerBalance = Wallet::withBalance()->find($wallet->id)->balance;

    expect($eagerBalance)->toBe($accessorBalance);
    expect($eagerBalance)->toBe(899.50);
});

test('wallet API returns correct formatted balance', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 100.50,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v0/wallets/{$wallet->id}?company_id={$this->company->id}");

    $response->assertOk()
        ->assertJsonPath('data.balance', '100.50');
});

test('balance remains zero when credits equal debits', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 100,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -100,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    expect($wallet->fresh()->balance)->toBe(0.0);
});

test('balance handles decimal precision correctly', function () {
    $wallet = Wallet::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    // 10.50 + 100.50 - 10.50 = 100.50
    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 10.50,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'to_wallet_id' => $wallet->id,
        'from_wallet_id' => null,
        'amount' => 100.50,
        'type' => TransactionType::Credit,
        'external' => true,
    ]);

    Transaction::factory()->create([
        'from_wallet_id' => $wallet->id,
        'to_wallet_id' => null,
        'amount' => -10.50,
        'type' => TransactionType::Debit,
        'external' => true,
    ]);

    expect($wallet->fresh()->balance)->toBe(100.50);

    // Eager loaded should match
    $eagerBalance = Wallet::withBalance()->find($wallet->id)->balance;
    expect($eagerBalance)->toBe(100.50);
});
