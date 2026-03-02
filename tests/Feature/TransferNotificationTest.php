<?php

use App\Models\UserSetting;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionCompleted;
use App\Notifications\TransactionPendingApproval;
use App\Notifications\TransactionRejected;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => transferSetup());

// ── Completed Transfer Notification ──────────────────────────────

test('completed transfer sends TransactionCompleted to initiator', function () {
    Notification::fake();

    createInternalTransfer(['amount' => 500, 'reference' => 'Notification test'])
        ->assertStatus(201);

    Notification::assertSentTo($this->member, TransactionCompleted::class);
});

test('completed transfer does not send TransactionCompleted when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_money_sent' => false,
    ]);

    createInternalTransfer(['amount' => 500, 'reference' => 'No notification test'])
        ->assertStatus(201);

    Notification::assertNotSentTo($this->member, TransactionCompleted::class);
});

// ── Pending Approval Notification ────────────────────────────────

test('pending transfer sends TransactionPendingApproval to admins and managers', function () {
    Notification::fake();

    createInternalTransfer(['amount' => 15000, 'reference' => 'Approval notification test'])
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

    createInternalTransfer(['amount' => 15000, 'reference' => 'Admin opt-out test'])
        ->assertStatus(201);

    Notification::assertNotSentTo($this->admin, TransactionPendingApproval::class);
    Notification::assertSentTo($this->manager, TransactionPendingApproval::class);
});

// ── Approved Transfer Notification ───────────────────────────────

test('approved transfer sends TransactionApproved to initiator', function () {
    Notification::fake();

    $groupId = createPendingTransfer(['reference' => 'Approve notification test']);

    Notification::fake();

    reviewTransfer($groupId, 'approve')->assertOk();

    Notification::assertSentTo($this->member, TransactionApproved::class);
    Notification::assertNotSentTo($this->member, TransactionCompleted::class);
});

test('approved transfer does not notify when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_transaction_approved' => false,
    ]);

    $groupId = createPendingTransfer(['reference' => 'Opt-out approve test']);

    Notification::fake();

    reviewTransfer($groupId, 'approve')->assertOk();

    Notification::assertNotSentTo($this->member, TransactionApproved::class);
});

// ── Rejected Transfer Notification ───────────────────────────────

test('rejected transfer sends TransactionRejected to initiator', function () {
    Notification::fake();

    $groupId = createPendingTransfer(['reference' => 'Reject notification test']);

    Notification::fake();

    reviewTransfer($groupId, 'reject', ['reason' => 'Suspicious activity'])->assertOk();

    Notification::assertSentTo($this->member, TransactionRejected::class);
});

test('rejected transfer does not notify when setting is off', function () {
    Notification::fake();

    UserSetting::create([
        'user_id' => $this->member->id,
        'notify_transaction_rejected' => false,
    ]);

    $groupId = createPendingTransfer(['reference' => 'Opt-out reject test']);

    Notification::fake();

    reviewTransfer($groupId, 'reject', ['reason' => 'Unauthorized'])->assertOk();

    Notification::assertNotSentTo($this->member, TransactionRejected::class);
});
