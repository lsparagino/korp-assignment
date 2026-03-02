<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

if (! defined('TRANSFERS_ENDPOINT')) {
    define('TRANSFERS_ENDPOINT', '/api/v0/transfers');
}

function idempotencyHeaders(): array
{
    return ['Idempotency-Key' => Str::uuid()->toString()];
}

beforeEach(function () {
    $this->company = Company::factory()->create();

    $this->member = User::factory()->create(['role' => 'member']);
    $this->member->companies()->attach($this->company);

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->admin->companies()->attach($this->company);

    $this->manager = User::factory()->create(['role' => 'manager']);
    $this->manager->companies()->attach($this->company);

    $this->senderWallet = Wallet::factory()->create([
        'user_id' => $this->member->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);

    $this->receiverWallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);

    // Fund sender wallet with $20,000
    Transaction::factory()->create([
        'wallet_id' => $this->senderWallet->id,
        'type' => TransactionType::Credit,
        'amount' => 20000,
        'external' => true,
        'status' => 'completed',
    ]);

    // Set a USD approval threshold of $10,000
    CompanySetting::create([
        'company_id' => $this->company->id,
        'currency' => 'USD',
        'approval_threshold' => 10000,
    ]);
});

// ── Internal Transfer Tests ──────────────────────────────────────

test('internal transfer auto-approved below threshold', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Office supplies',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');

    expect($this->senderWallet->fresh()->balance)->toBe(19500.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(500.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);
});

test('internal transfer pending above threshold', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Equipment purchase',
            'notes' => 'Needed for the new office setup',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'pending_approval');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);
});

test('transfer completes immediately when no threshold is configured for currency', function () {
    $eurSender = Wallet::factory()->create([
        'user_id' => $this->member->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::EUR,
        'status' => 'active',
    ]);
    $eurReceiver = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::EUR,
        'status' => 'active',
    ]);
    Transaction::factory()->create([
        'wallet_id' => $eurSender->id,
        'type' => TransactionType::Credit,
        'amount' => 50000,
        'external' => true,
        'status' => 'completed',
    ]);

    // No CompanySetting for EUR — large transfer should complete immediately
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $eurSender->id,
            'receiver_wallet_id' => $eurReceiver->id,
            'amount' => 25000,
            'external' => false,
            'reference' => 'No threshold test',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
    expect((float) $eurSender->fresh()->locked_balance)->toBe(0.0);
});

// ── External Transfer Tests ──────────────────────────────────────

test('external transfer auto-approved creates only debit row', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'amount' => 500,
            'external' => true,
            'external_address' => 'bc1qexternaladdress123',
            'external_name' => 'My External Wallet',
            'reference' => 'External payout',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');

    $groupId = $response->json('data.group_id');
    $transactions = Transaction::where('group_id', $groupId)->get();

    expect($transactions)->toHaveCount(1);
    expect($transactions->first()->type)->toBe(TransactionType::Debit);
    expect($transactions->first()->external_address)->toBe('bc1qexternaladdress123');
    expect($transactions->first()->external_name)->toBe('My External Wallet');
    expect($this->senderWallet->fresh()->balance)->toBe(19500.0);
});

// ── Role Bypass Tests ────────────────────────────────────────────

test('admin bypasses approval threshold', function () {
    $adminWallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);
    Transaction::factory()->create([
        'wallet_id' => $adminWallet->id,
        'type' => TransactionType::Credit,
        'amount' => 50000,
        'external' => true,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $adminWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Admin transfer',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
});

test('manager bypasses approval threshold', function () {
    $managerWallet = Wallet::factory()->create([
        'user_id' => $this->manager->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
        'status' => 'active',
    ]);
    Transaction::factory()->create([
        'wallet_id' => $managerWallet->id,
        'type' => TransactionType::Credit,
        'amount' => 50000,
        'external' => true,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $managerWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Manager transfer',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
});

// ── Validation Tests ─────────────────────────────────────────────

test('insufficient funds returns 422', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 999999,
            'external' => false,
            'reference' => 'Big transfer',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('amount');
});

test('insufficient available funds due to locked balance returns 422', function () {
    // Lock most funds via a pending transfer
    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'First lock',
            'company_id' => $this->company->id,
        ]);

    // Try to send more than available (20000 - 15000 = 5000 available)
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 6000,
            'external' => false,
            'reference' => 'Second attempt',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('amount');
});

test('cross-currency internal transfer returns 422', function () {
    $eurWallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::EUR,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $eurWallet->id,
            'amount' => 100,
            'external' => false,
            'reference' => 'Cross-currency test',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('receiver_wallet_id');
});

// ── Manager Review Tests ─────────────────────────────────────────

test('manager can approve a pending transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Pending approval',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $reviewResponse = $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ]);

    $reviewResponse->assertOk();
    $reviewResponse->assertJsonPath('data.status', 'completed');

    expect($this->senderWallet->fresh()->balance)->toBe(5000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(15000.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);
});

test('manager can reject a pending transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Pending rejection',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $reviewResponse = $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'reject',
            'reason' => 'Suspicious activity',
            'company_id' => $this->company->id,
        ]);

    $reviewResponse->assertOk();
    $reviewResponse->assertJsonPath('data.status', 'rejected');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);

    $transaction = Transaction::where('group_id', $groupId)->first();
    expect($transaction->reject_reason)->toBe('Suspicious activity');
});

test('double approval returns 409 conflict', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Double approval test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    $secondManager = User::factory()->create(['role' => 'manager']);
    $secondManager->companies()->attach($this->company);

    $this->actingAs($secondManager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(409);
});

test('member cannot review a transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Member review test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(403);
});

// ── Frozen Wallet Tests ──────────────────────────────────────────

test('frozen sender wallet returns 403', function () {
    $this->senderWallet->update(['status' => 'frozen']);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Frozen sender test',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
});

test('frozen receiver wallet returns 403', function () {
    $this->receiverWallet->update(['status' => 'frozen']);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Frozen receiver test',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
});

test('frozen sender wallet on external transfer returns 403', function () {
    $this->senderWallet->update(['status' => 'frozen']);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'amount' => 500,
            'external' => true,
            'external_address' => 'bc1qfrozentest',
            'external_name' => 'Frozen Test',
            'reference' => 'Frozen external test',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
});

test('transaction resource includes initiator and reviewer names', function () {
    // Create a pending transfer as a member
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Initiator test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    // Approve as manager
    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    // Fetch transactions and verify initiator/reviewer names are present
    $indexResponse = $this->actingAs($this->member, 'sanctum')
        ->getJson("/api/v0/transactions?company_id={$this->company->id}");

    $indexResponse->assertOk();

    $transactions = $indexResponse->json('data');
    $reviewed = collect($transactions)->firstWhere('group_id', $groupId);

    expect($reviewed)->not->toBeNull();
    expect($reviewed['initiator']['name'])->toBe($this->member->name);
    expect($reviewed['reviewer']['name'])->toBe($this->manager->name);
});

// ── Daily Transaction Limit Tests ────────────────────────────────

test('transfer rejected when daily limit exceeded', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'daily_transaction_limit' => 1000,
    ]);

    // First transfer at $800 — should work
    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 800,
            'external' => false,
            'reference' => 'Under limit',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);

    // Second transfer at $300 would make total $1100 — should fail
    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 300,
            'external' => false,
            'reference' => 'Over limit',
            'company_id' => $this->company->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('transfer allowed when under daily limit', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'daily_transaction_limit' => 5000,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Under limit',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);
});

test('transfer allowed when no daily limit is set', function () {
    // No UserSetting → no limit
    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 9999,
            'external' => false,
            'reference' => 'No limit set',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);
});

// ── Security Threshold Tests ─────────────────────────────────────

test('transfer above security threshold rejected without password', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 600,
            'external' => false,
            'reference' => 'Above threshold',
            'company_id' => $this->company->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identity']);
});

test('transfer above security threshold succeeds with password', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 600,
            'external' => false,
            'reference' => 'With password',
            'password' => 'password',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);
});

test('transfer below security threshold needs no verification', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 400,
            'external' => false,
            'reference' => 'Below threshold',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);
});

test('transfer with no security threshold needs no verification', function () {
    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 9000,
            'external' => false,
            'reference' => 'No threshold',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);
});

// ── Cancellation Tests ───────────────────────────────────────────

test('initiator can cancel a pending transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Cancel me',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);

    $cancelResponse = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/cancel", [
            'company_id' => $this->company->id,
        ]);

    $cancelResponse->assertOk();
    $cancelResponse->assertJsonPath('data.status', 'cancelled');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);

    $transaction = Transaction::withoutGlobalScopes()->where('group_id', $groupId)->first();
    expect($transaction->status->value)->toBe('cancelled');
});

test('non-initiator cannot cancel a transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Not yours to cancel',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/cancel", [
            'company_id' => $this->company->id,
        ])
        ->assertStatus(403);

    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);
});

test('cannot cancel an already reviewed transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Already approved',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/cancel", [
            'company_id' => $this->company->id,
        ])
        ->assertStatus(409);
});

test('initiator with any role can cancel their own pending transfer', function () {
    // Create a scenario where a manager has a pending transfer
    // (e.g., future feature where even manager transfers require approval)
    $managerWallet = Wallet::factory()->create([
        'user_id' => $this->manager->id,
        'company_id' => $this->company->id,
        'currency' => \App\Enums\WalletCurrency::USD,
        'status' => 'active',
    ]);
    Transaction::factory()->create([
        'wallet_id' => $managerWallet->id,
        'type' => TransactionType::Credit,
        'amount' => 50000,
        'external' => true,
        'status' => 'completed',
    ]);

    // Manually create a pending transfer to simulate future behaviour
    $groupId = \Illuminate\Support\Str::uuid()->toString();
    Transaction::create([
        'group_id' => $groupId,
        'wallet_id' => $managerWallet->id,
        'counterpart_wallet_id' => $this->receiverWallet->id,
        'type' => TransactionType::Debit,
        'amount' => -15000,
        'external' => false,
        'reference' => 'Manager pending',
        'status' => 'pending_approval',
        'currency' => 'USD',
        'exchange_rate' => 1,
        'initiator_user_id' => $this->manager->id,
    ]);
    $managerWallet->increment('locked_balance', 15000);

    $cancelResponse = $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/cancel", [
            'company_id' => $this->company->id,
        ]);

    $cancelResponse->assertOk();
    $cancelResponse->assertJsonPath('data.status', 'cancelled');
    expect((float) $managerWallet->fresh()->locked_balance)->toBe(0.0);
});

// ── Idempotency Tests ────────────────────────────────────────────

test('duplicate idempotency key returns cached response without creating duplicate', function () {
    $idempotencyKey = Str::uuid()->toString();
    $payload = [
        'sender_wallet_id' => $this->senderWallet->id,
        'receiver_wallet_id' => $this->receiverWallet->id,
        'amount' => 500,
        'external' => false,
        'reference' => 'Idempotent transfer',
        'company_id' => $this->company->id,
    ];

    $first = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson(TRANSFERS_ENDPOINT, $payload);

    $first->assertStatus(201);

    $second = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson(TRANSFERS_ENDPOINT, $payload);

    $second->assertStatus(201);
    expect($second->json())->toBe($first->json());

    $transactionCount = Transaction::where('reference', 'Idempotent transfer')->count();
    expect($transactionCount)->toBe(2); // debit + credit pair, not doubled
});

test('transfer without idempotency key returns 400', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Missing key',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(400);
    $response->assertJsonPath('message', __('messages.idempotency_key_required'));
});
