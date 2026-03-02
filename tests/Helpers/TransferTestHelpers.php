<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

if (defined('TRANSFERS_ENDPOINT')) {
    return;
}

const TRANSFERS_ENDPOINT = '/api/v0/transfers';

function idempotencyHeaders(): array
{
    return ['Idempotency-Key' => Str::uuid()->toString()];
}

/**
 * Shared beforeEach setup for transfer tests.
 * Creates company, member, admin, manager, sender/receiver wallets,
 * funds the sender with $20,000, and sets a $10,000 USD approval threshold.
 */
function transferSetup(): void
{
    test()->company = Company::factory()->create();

    test()->member = User::factory()->create(['role' => 'member']);
    test()->member->companies()->attach(test()->company);

    test()->admin = User::factory()->create(['role' => 'admin']);
    test()->admin->companies()->attach(test()->company);

    test()->manager = User::factory()->create(['role' => 'manager']);
    test()->manager->companies()->attach(test()->company);

    test()->senderWallet = Wallet::factory()->create([
        'user_id' => test()->member->id,
        'company_id' => test()->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);

    test()->receiverWallet = Wallet::factory()->create([
        'user_id' => test()->admin->id,
        'company_id' => test()->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);

    Transaction::factory()->create([
        'wallet_id' => test()->senderWallet->id,
        'type' => TransactionType::Credit,
        'amount' => 20000,
        'external' => true,
        'status' => 'completed',
    ]);

    CompanySetting::create([
        'company_id' => test()->company->id,
        'currency' => 'USD',
        'approval_threshold' => 10000,
    ]);
}

/**
 * Create a wallet for a user, fund it, and return the wallet.
 */
function createWalletAndFund(User $user, float $amount, WalletCurrency $currency = WalletCurrency::USD): Wallet
{
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'company_id' => test()->company->id,
        'currency' => $currency,
        'status' => 'active',
    ]);

    Transaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => TransactionType::Credit,
        'amount' => $amount,
        'external' => true,
        'status' => 'completed',
    ]);

    return $wallet;
}

/**
 * Submit an internal transfer as the given user (defaults to member).
 */
function createInternalTransfer(array $overrides = [], ?User $actingAs = null): TestResponse
{
    $user = $actingAs ?? test()->member;

    $payload = array_merge([
        'sender_wallet_id' => test()->senderWallet->id,
        'receiver_wallet_id' => test()->receiverWallet->id,
        'amount' => 500,
        'external' => false,
        'reference' => 'Test transfer',
        'company_id' => test()->company->id,
    ], $overrides);

    return test()->actingAs($user, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, $payload);
}

/**
 * Submit an external transfer as the given user (defaults to member).
 */
function createExternalTransfer(array $overrides = [], ?User $actingAs = null): TestResponse
{
    $user = $actingAs ?? test()->member;

    $payload = array_merge([
        'sender_wallet_id' => test()->senderWallet->id,
        'amount' => 500,
        'external' => true,
        'external_address' => 'bc1qexternaladdress123',
        'external_name' => 'My External Wallet',
        'reference' => 'External transfer',
        'company_id' => test()->company->id,
    ], $overrides);

    return test()->actingAs($user, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, $payload);
}

/**
 * Create a pending transfer (above threshold) and return the group_id.
 */
function createPendingTransfer(array $overrides = []): string
{
    $response = createInternalTransfer(array_merge([
        'amount' => 15000,
        'reference' => 'Pending transfer',
    ], $overrides));

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'pending_approval');

    return $response->json('data.group_id');
}

/**
 * Review (approve/reject) a pending transfer as a manager.
 */
function reviewTransfer(string $groupId, string $action, array $extra = []): TestResponse
{
    $payload = array_merge([
        'action' => $action,
        'company_id' => test()->company->id,
    ], $extra);

    return test()->actingAs(test()->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", $payload);
}

/**
 * Cancel a pending transfer as the given user.
 */
function cancelTransfer(string $groupId, ?User $actingAs = null): TestResponse
{
    $user = $actingAs ?? test()->member;

    return test()->actingAs($user, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/cancel", [
            'company_id' => test()->company->id,
        ]);
}
