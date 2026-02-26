<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $member;

    private User $admin;

    private User $manager;

    private Wallet $senderWallet;

    private Wallet $receiverWallet;

    protected function setUp(): void
    {
        parent::setUp();

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
        ]);

        $this->receiverWallet = Wallet::factory()->create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'currency' => WalletCurrency::USD,
        ]);

        // Fund the sender wallet with $20,000
        Transaction::factory()->create([
            'wallet_id' => $this->senderWallet->id,
            'type' => TransactionType::Credit,
            'amount' => 20000,
            'external' => true,
            'status' => 'completed',
        ]);
    }

    // ── Internal Transfer Tests ──────────────────────────────────────

    public function test_internal_transfer_auto_approved_below_threshold(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 500,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'completed');

        // Sender balance decreased
        $this->assertEquals(19500.0, $this->senderWallet->fresh()->balance);
        // Receiver balance increased
        $this->assertEquals(500.0, $this->receiverWallet->fresh()->balance);
        // No locked balance
        $this->assertEquals(0.0, (float) $this->senderWallet->fresh()->locked_balance);
    }

    public function test_internal_transfer_pending_above_threshold(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'pending_approval');

        // Balance unchanged (pending doesn't affect computed balance)
        $this->assertEquals(20000.0, $this->senderWallet->fresh()->balance);
        // Receiver NOT yet credited
        $this->assertEquals(0.0, $this->receiverWallet->fresh()->balance);
        // Funds locked
        $this->assertEquals(15000.0, (float) $this->senderWallet->fresh()->locked_balance);
    }

    // ── External Transfer Tests ──────────────────────────────────────

    public function test_external_transfer_auto_approved(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'amount' => 500,
                'external' => true,
                'external_address' => 'bc1qexternaladdress123',
                'external_name' => 'My External Wallet',
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'completed');

        // Only 1 debit row created, no credit row
        $groupId = $response->json('data.group_id');
        $txns = Transaction::where('group_id', $groupId)->get();
        $this->assertCount(1, $txns);
        $this->assertEquals(TransactionType::Debit, $txns->first()->type);
        $this->assertEquals('bc1qexternaladdress123', $txns->first()->external_address);
        $this->assertEquals('My External Wallet', $txns->first()->external_name);

        // Sender balance decreased
        $this->assertEquals(19500.0, $this->senderWallet->fresh()->balance);
    }

    // ── Role Bypass Tests ────────────────────────────────────────────

    public function test_admin_bypasses_threshold(): void
    {
        // Give admin a funded wallet
        $adminWallet = Wallet::factory()->create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'currency' => WalletCurrency::USD,
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
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'completed');
    }

    public function test_manager_bypasses_threshold(): void
    {
        $managerWallet = Wallet::factory()->create([
            'user_id' => $this->manager->id,
            'company_id' => $this->company->id,
            'currency' => WalletCurrency::USD,
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
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'completed');
    }

    // ── Validation Tests ─────────────────────────────────────────────

    public function test_insufficient_funds_returns_422(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 999999,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function test_insufficient_available_funds_locked_returns_422(): void
    {
        // First, lock most funds by creating a pending transfer
        $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        // Now try to send more than available (20000 - 15000 = 5000 available)
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 6000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    public function test_cross_currency_internal_rejected(): void
    {
        $eurWallet = Wallet::factory()->create([
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'currency' => WalletCurrency::EUR,
        ]);

        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $eurWallet->id,
                'amount' => 100,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('receiver_wallet_id');
    }

    // ── Manager Review Tests ─────────────────────────────────────────

    public function test_manager_approve_pending_transfer(): void
    {
        // Create pending transfer
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $groupId = $response->json('data.group_id');

        // Manager approves
        $reviewResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v0/transfers/{$groupId}/review", [
                'action' => 'approve',
                'company_id' => $this->company->id,
            ]);

        $reviewResponse->assertOk();
        $reviewResponse->assertJsonPath('data.status', 'completed');

        // Balances updated
        $this->assertEquals(5000.0, $this->senderWallet->fresh()->balance);
        $this->assertEquals(15000.0, $this->receiverWallet->fresh()->balance);
        // Locked balance released
        $this->assertEquals(0.0, (float) $this->senderWallet->fresh()->locked_balance);
    }

    public function test_manager_reject_pending_transfer(): void
    {
        // Create pending transfer
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $groupId = $response->json('data.group_id');

        // Manager rejects
        $reviewResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v0/transfers/{$groupId}/review", [
                'action' => 'reject',
                'reason' => 'Suspicious activity',
                'company_id' => $this->company->id,
            ]);

        $reviewResponse->assertOk();
        $reviewResponse->assertJsonPath('data.status', 'rejected');

        // Balances unchanged
        $this->assertEquals(20000.0, $this->senderWallet->fresh()->balance);
        $this->assertEquals(0.0, $this->receiverWallet->fresh()->balance);
        // Locked balance released
        $this->assertEquals(0.0, (float) $this->senderWallet->fresh()->locked_balance);
        // Reject reason stored
        $tx = Transaction::where('group_id', $groupId)->first();
        $this->assertEquals('Suspicious activity', $tx->reject_reason);
    }

    public function test_double_approve_returns_409(): void
    {
        // Create pending transfer
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $groupId = $response->json('data.group_id');

        // First manager approves
        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v0/transfers/{$groupId}/review", [
                'action' => 'approve',
                'company_id' => $this->company->id,
            ])
            ->assertOk();

        // Second manager tries to approve — should get 409
        $secondManager = User::factory()->create(['role' => 'manager']);
        $secondManager->companies()->attach($this->company);

        $conflictResponse = $this->actingAs($secondManager, 'sanctum')
            ->postJson("/api/v0/transfers/{$groupId}/review", [
                'action' => 'approve',
                'company_id' => $this->company->id,
            ]);

        $conflictResponse->assertStatus(409);
    }

    public function test_member_cannot_review_transfer(): void
    {
        // Create pending transfer
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/v0/transfers', [
                'sender_wallet_id' => $this->senderWallet->id,
                'receiver_wallet_id' => $this->receiverWallet->id,
                'amount' => 15000,
                'external' => false,
                'company_id' => $this->company->id,
            ]);

        $groupId = $response->json('data.group_id');

        // Member tries to review — should be forbidden
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson("/api/v0/transfers/{$groupId}/review", [
                'action' => 'approve',
                'company_id' => $this->company->id,
            ]);

        $response->assertStatus(403);
    }
}
