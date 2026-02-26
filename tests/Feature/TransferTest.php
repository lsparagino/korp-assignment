<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

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
});

// ── Internal Transfer Tests ──────────────────────────────────────

test('internal transfer auto-approved below threshold', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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

// ── External Transfer Tests ──────────────────────────────────────

test('external transfer auto-approved creates only debit row', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'First lock',
            'company_id' => $this->company->id,
        ]);

    // Try to send more than available (20000 - 15000 = 5000 available)
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Pending approval',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $reviewResponse = $this->actingAs($this->manager, 'sanctum')
        ->postJson("/api/v0/transfers/{$groupId}/review", [
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
        ->postJson('/api/v0/transfers', [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Pending rejection',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $reviewResponse = $this->actingAs($this->manager, 'sanctum')
        ->postJson("/api/v0/transfers/{$groupId}/review", [
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
        ->postJson('/api/v0/transfers', [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Double approval test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->manager, 'sanctum')
        ->postJson("/api/v0/transfers/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    $secondManager = User::factory()->create(['role' => 'manager']);
    $secondManager->companies()->attach($this->company);

    $this->actingAs($secondManager, 'sanctum')
        ->postJson("/api/v0/transfers/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(409);
});

test('member cannot review a transfer', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v0/transfers', [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Member review test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    $this->actingAs($this->member, 'sanctum')
        ->postJson("/api/v0/transfers/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(403);
});

// ── Frozen Wallet Tests ──────────────────────────────────────────

test('frozen sender wallet returns 403', function () {
    $this->senderWallet->update(['status' => 'frozen']);

    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
        ->postJson('/api/v0/transfers', [
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
