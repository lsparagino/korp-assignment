<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFilterTest extends TestCase
{
    use RefreshDatabase;

    private const TRANSACTIONS_ENDPOINT = '/api/v0/transactions';

    private const BASE_DATE = '2025-01-01 10:00:00';

    private const SECOND_DATE = '2025-02-01 10:00:00';

    private const MID_DATE = '2025-01-15 10:00:00';

    private User $user;

    private Wallet $wallet;

    private \App\Models\Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = \App\Models\Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_filter_transactions_by_type(): void
    {
        Transaction::factory()->count(3)->create([
            'wallet_id' => $this->wallet->id,
            'type' => TransactionType::Credit,
        ]);

        Transaction::factory()->count(2)->create([
            'wallet_id' => $this->wallet->id,
            'type' => TransactionType::Debit,
        ]);

        // Filter by Credit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?type=credit&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('credit', $transaction['type']);
        }

        // Filter by Debit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?type=debit&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('debit', $transaction['type']);
        }
    }

    public function test_can_filter_transactions_by_date_from(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::BASE_DATE,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::SECOND_DATE,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?date_from=2025-01-15&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals(self::SECOND_DATE, Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_date_to(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::BASE_DATE,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::SECOND_DATE,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?date_to=2025-01-15&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals(self::BASE_DATE, Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_date_range(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::BASE_DATE,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::MID_DATE,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'created_at' => self::SECOND_DATE,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?date_from=2025-01-10&date_to=2025-01-20&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals(self::MID_DATE, Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_amount_range(): void
    {
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'amount' => 50]);
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'amount' => 150]);
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'amount' => 250]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?amount_min=100&amount_max=200&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals(150, (float) Transaction::find($response->json('data.0.id'))->amount);
    }

    public function test_can_filter_transactions_by_reference(): void
    {
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'reference' => 'Invoice 123']);
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'reference' => 'Refund 456']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?reference=Refund&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertStringContainsString('Refund', Transaction::find($response->json('data.0.id'))->reference);
    }

    public function test_can_filter_transactions_by_wallets(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);
        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);

        $groupId = \Illuminate\Support\Str::uuid()->toString();
        Transaction::factory()->create(['group_id' => $groupId, 'wallet_id' => $wallet1->id, 'counterpart_wallet_id' => $wallet2->id, 'type' => TransactionType::Debit, 'amount' => -100, 'external' => false]);
        Transaction::factory()->create(['group_id' => $groupId, 'wallet_id' => $wallet2->id, 'counterpart_wallet_id' => $wallet1->id, 'type' => TransactionType::Credit, 'amount' => 100, 'external' => false]);

        // Filter by wallet_id — should return the debit entry for wallet1 (de-duplicated)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?wallet_id={$wallet1->id}&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($wallet1->id, $response->json('data.0.wallet_id'));

        // Filter by counterpart_wallet_id — the debit entry has wallet2 as counterpart
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?counterpart_wallet_id={$wallet2->id}&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($wallet2->id, $response->json('data.0.counterpart_wallet_id'));
    }

    public function test_guests_are_denied_access_to_transactions(): void
    {
        $response = $this->getJson(self::TRANSACTIONS_ENDPOINT);

        $response->assertUnauthorized();
    }

    public function test_transactions_are_paginated(): void
    {
        Transaction::factory()->count(15)->create([
            'wallet_id' => $this->wallet->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?per_page=5&company_id={$this->company->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15);
    }

    public function test_internal_transfers_are_deduplicated(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);
        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);

        $groupId = \Illuminate\Support\Str::uuid()->toString();
        Transaction::factory()->create(['group_id' => $groupId, 'wallet_id' => $wallet1->id, 'counterpart_wallet_id' => $wallet2->id, 'type' => TransactionType::Debit, 'amount' => -100, 'external' => false]);
        Transaction::factory()->create(['group_id' => $groupId, 'wallet_id' => $wallet2->id, 'counterpart_wallet_id' => $wallet1->id, 'type' => TransactionType::Credit, 'amount' => 100, 'external' => false]);

        // User owns both wallets — should see only 1 row (the debit entry)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('debit', $response->json('data.0.type'));
    }

    public function test_can_filter_transactions_by_status_completed(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::Completed,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::PendingApproval,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::Rejected,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?status=completed&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('completed', $response->json('data.0.status'));
    }

    public function test_can_filter_transactions_by_status_pending_approval(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::Completed,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::PendingApproval,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?status=pending_approval&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('pending_approval', $response->json('data.0.status'));
    }

    public function test_can_filter_transactions_by_status_rejected(): void
    {
        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::Completed,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'status' => TransactionStatus::Rejected,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?status=rejected&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('rejected', $response->json('data.0.status'));
    }

    public function test_can_filter_transactions_by_initiator_user_id(): void
    {
        $initiator = User::factory()->create();
        $initiator->companies()->attach($this->company);

        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->company);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'initiator_user_id' => $initiator->id,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'initiator_user_id' => $otherUser->id,
        ]);

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'initiator_user_id' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?initiator_user_id={$initiator->id}&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($initiator->id, $response->json('data.0.initiator_user_id'));
    }
}
