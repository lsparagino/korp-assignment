<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

beforeEach(fn () => transferSetup());

// ── Internal Transfer Tests ──────────────────────────────────────

test('internal transfer auto-approved below threshold', function () {
    $response = createInternalTransfer(['amount' => 500, 'reference' => 'Office supplies']);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');

    expect($this->senderWallet->fresh()->balance)->toBe(19500.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(500.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);
});

test('internal transfer pending above threshold', function () {
    $response = createInternalTransfer([
        'amount' => 15000,
        'reference' => 'Equipment purchase',
        'notes' => 'Needed for the new office setup',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'pending_approval');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);
});

test('transfer completes immediately when no threshold is configured for currency', function () {
    $eurSender = createWalletAndFund($this->member, 50000, WalletCurrency::EUR);
    $eurReceiver = createWalletAndFund($this->admin, 0, WalletCurrency::EUR);

    $response = createInternalTransfer([
        'sender_wallet_id' => $eurSender->id,
        'receiver_wallet_id' => $eurReceiver->id,
        'amount' => 25000,
        'reference' => 'No threshold test',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
    expect((float) $eurSender->fresh()->locked_balance)->toBe(0.0);
});

// ── External Transfer Tests ──────────────────────────────────────

test('external transfer auto-approved creates only debit row', function () {
    $response = createExternalTransfer([
        'amount' => 500,
        'reference' => 'External payout',
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
    $adminWallet = createWalletAndFund($this->admin, 50000);

    $response = createInternalTransfer([
        'sender_wallet_id' => $adminWallet->id,
        'amount' => 15000,
        'reference' => 'Admin transfer',
    ], $this->admin);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
});

test('manager bypasses approval threshold', function () {
    $managerWallet = createWalletAndFund($this->manager, 50000);

    $response = createInternalTransfer([
        'sender_wallet_id' => $managerWallet->id,
        'amount' => 15000,
        'reference' => 'Manager transfer',
    ], $this->manager);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', 'completed');
});

// ── Validation Tests ─────────────────────────────────────────────

test('insufficient funds returns 422', function () {
    $response = createInternalTransfer(['amount' => 999999, 'reference' => 'Big transfer']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('amount');
});

test('insufficient available funds due to locked balance returns 422', function () {
    createInternalTransfer(['amount' => 15000, 'reference' => 'First lock']);

    $response = createInternalTransfer(['amount' => 6000, 'reference' => 'Second attempt']);

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

    $response = createInternalTransfer([
        'receiver_wallet_id' => $eurWallet->id,
        'amount' => 100,
        'reference' => 'Cross-currency test',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('receiver_wallet_id');
});

// ── Manager Review Tests ─────────────────────────────────────────

test('manager can approve a pending transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Pending approval']);

    $reviewResponse = reviewTransfer($groupId, 'approve');

    $reviewResponse->assertOk();
    $reviewResponse->assertJsonPath('data.status', 'completed');

    expect($this->senderWallet->fresh()->balance)->toBe(5000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(15000.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);
});

test('manager can reject a pending transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Pending rejection']);

    $reviewResponse = reviewTransfer($groupId, 'reject', ['reason' => 'Suspicious activity']);

    $reviewResponse->assertOk();
    $reviewResponse->assertJsonPath('data.status', 'rejected');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);

    $transaction = Transaction::where('group_id', $groupId)->first();
    expect($transaction->reject_reason)->toBe('Suspicious activity');
});

test('double approval returns 409 conflict', function () {
    $groupId = createPendingTransfer(['reference' => 'Double approval test']);

    reviewTransfer($groupId, 'approve')->assertOk();

    $secondManager = User::factory()->create(['role' => 'manager']);
    $secondManager->companies()->attach($this->company);

    test()->actingAs($secondManager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(409);
});

test('member cannot review a transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Member review test']);

    test()->actingAs($this->member, 'sanctum')
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

    $response = createInternalTransfer(['amount' => 500, 'reference' => 'Frozen sender test']);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
});

test('frozen receiver wallet returns 403', function () {
    $this->receiverWallet->update(['status' => 'frozen']);

    $response = createInternalTransfer(['amount' => 500, 'reference' => 'Frozen receiver test']);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect($this->receiverWallet->fresh()->balance)->toBe(0.0);
});

test('frozen sender wallet on external transfer returns 403', function () {
    $this->senderWallet->update(['status' => 'frozen']);

    $response = createExternalTransfer(['reference' => 'Frozen external test']);

    $response->assertStatus(403);
    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
});

test('transaction resource includes initiator and reviewer names', function () {
    $groupId = createPendingTransfer(['reference' => 'Initiator test']);

    reviewTransfer($groupId, 'approve')->assertOk();

    $indexResponse = test()->actingAs($this->member, 'sanctum')
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

    createInternalTransfer(['amount' => 800, 'reference' => 'Under limit'])->assertStatus(201);

    createInternalTransfer(['amount' => 300, 'reference' => 'Over limit'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('transfer allowed when under daily limit', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'daily_transaction_limit' => 5000,
    ]);

    createInternalTransfer(['amount' => 500, 'reference' => 'Under limit'])->assertStatus(201);
});

test('transfer allowed when no daily limit is set', function () {
    createInternalTransfer(['amount' => 9999, 'reference' => 'No limit set'])->assertStatus(201);
});

// ── Security Threshold Tests ─────────────────────────────────────

test('transfer above security threshold rejected without password', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    createInternalTransfer(['amount' => 600, 'reference' => 'Above threshold'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identity']);
});

test('transfer above security threshold succeeds with password', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    createInternalTransfer([
        'amount' => 600,
        'reference' => 'With password',
        'password' => 'password',
    ])->assertStatus(201);
});

test('transfer below security threshold needs no verification', function () {
    \App\Models\UserSetting::create([
        'user_id' => $this->member->id,
        'security_threshold' => 500,
    ]);

    createInternalTransfer(['amount' => 400, 'reference' => 'Below threshold'])->assertStatus(201);
});

test('transfer with no security threshold needs no verification', function () {
    createInternalTransfer(['amount' => 9000, 'reference' => 'No threshold'])->assertStatus(201);
});

// ── Cancellation Tests ───────────────────────────────────────────

test('initiator can cancel a pending transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Cancel me']);

    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);

    $cancelResponse = cancelTransfer($groupId);

    $cancelResponse->assertOk();
    $cancelResponse->assertJsonPath('data.status', 'cancelled');

    expect($this->senderWallet->fresh()->balance)->toBe(20000.0);
    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(0.0);

    $transaction = Transaction::withoutGlobalScopes()->where('group_id', $groupId)->first();
    expect($transaction->status->value)->toBe('cancelled');
});

test('non-initiator cannot cancel a transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Not yours to cancel']);

    cancelTransfer($groupId, $this->manager)->assertStatus(403);

    expect((float) $this->senderWallet->fresh()->locked_balance)->toBe(15000.0);
});

test('cannot cancel an already reviewed transfer', function () {
    $groupId = createPendingTransfer(['reference' => 'Already approved']);

    reviewTransfer($groupId, 'approve')->assertOk();

    cancelTransfer($groupId)->assertStatus(409);
});

test('initiator with any role can cancel their own pending transfer', function () {
    $managerWallet = createWalletAndFund($this->manager, 50000);

    // Manually create a pending transfer to simulate future behaviour
    $groupId = Str::uuid()->toString();
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

    $cancelResponse = cancelTransfer($groupId, $this->manager);

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

    $first = test()->actingAs($this->member, 'sanctum')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson(TRANSFERS_ENDPOINT, $payload);

    $first->assertStatus(201);

    $second = test()->actingAs($this->member, 'sanctum')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson(TRANSFERS_ENDPOINT, $payload);

    $second->assertStatus(201);
    expect($second->json())->toBe($first->json());

    $transactionCount = Transaction::where('reference', 'Idempotent transfer')->count();
    expect($transactionCount)->toBe(2); // debit + credit pair, not doubled
});

test('transfer without idempotency key returns 400', function () {
    $response = test()->actingAs($this->member, 'sanctum')
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
