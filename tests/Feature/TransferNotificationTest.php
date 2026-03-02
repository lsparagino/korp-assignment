<?php

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Wallet;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionCompleted;
use App\Notifications\TransactionPendingApproval;
use App\Notifications\TransactionRejected;
use Illuminate\Support\Facades\Notification;

if (! defined('TRANSFERS_ENDPOINT')) {
    define('TRANSFERS_ENDPOINT', '/api/v0/transfers');
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

// ── Completed Transfer Notification ──────────────────────────────

test('completed transfer sends TransactionCompleted to initiator', function () {
    Notification::fake();

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'Notification test',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);

    Notification::assertSentTo($this->member, TransactionCompleted::class);
});

test('completed transfer does not send TransactionCompleted when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_money_sent' => false,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 500,
            'external' => false,
            'reference' => 'No notification test',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);

    Notification::assertNotSentTo($this->member, TransactionCompleted::class);
});

// ── Pending Approval Notification ────────────────────────────────

test('pending transfer sends TransactionPendingApproval to admins and managers', function () {
    Notification::fake();

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Approval notification test',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);

    Notification::assertSentTo($this->admin, TransactionPendingApproval::class);
    Notification::assertSentTo($this->manager, TransactionPendingApproval::class);
    Notification::assertNotSentTo($this->member, TransactionPendingApproval::class);
});

test('pending transfer does not notify admin when approval_needed setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->admin->id,
        'notify_approval_needed' => false,
    ]);

    $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Admin opt-out test',
            'company_id' => $this->company->id,
        ])
        ->assertStatus(201);

    Notification::assertNotSentTo($this->admin, TransactionPendingApproval::class);
    Notification::assertSentTo($this->manager, TransactionPendingApproval::class);
});

// ── Approved Transfer Notification ───────────────────────────────

test('approved transfer sends TransactionApproved to initiator', function () {
    Notification::fake();

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Approve notification test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    Notification::fake();

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    Notification::assertSentTo($this->member, TransactionApproved::class);
    Notification::assertNotSentTo($this->member, TransactionCompleted::class);
});

test('approved transfer does not notify when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_transaction_approved' => false,
    ]);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Opt-out approve test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    Notification::fake();

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'approve',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    Notification::assertNotSentTo($this->member, TransactionApproved::class);
});

// ── Rejected Transfer Notification ───────────────────────────────

test('rejected transfer sends TransactionRejected to initiator', function () {
    Notification::fake();

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Reject notification test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    Notification::fake();

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'reject',
            'reason' => 'Suspicious activity',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    Notification::assertSentTo($this->member, TransactionRejected::class);
});

test('rejected transfer does not notify when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_transaction_rejected' => false,
    ]);

    $response = $this->actingAs($this->member, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT, [
            'sender_wallet_id' => $this->senderWallet->id,
            'receiver_wallet_id' => $this->receiverWallet->id,
            'amount' => 15000,
            'external' => false,
            'reference' => 'Opt-out reject test',
            'company_id' => $this->company->id,
        ]);

    $groupId = $response->json('data.group_id');

    Notification::fake();

    $this->actingAs($this->manager, 'sanctum')
        ->withHeaders(idempotencyHeaders())
        ->postJson(TRANSFERS_ENDPOINT."/{$groupId}/review", [
            'action' => 'reject',
            'reason' => 'Unauthorized',
            'company_id' => $this->company->id,
        ])
        ->assertOk();

    Notification::assertNotSentTo($this->member, TransactionRejected::class);
});
