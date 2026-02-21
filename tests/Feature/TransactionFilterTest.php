<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFilterTest extends TestCase
{
    use RefreshDatabase;

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
            'to_wallet_id' => $this->wallet->id,
            'type' => TransactionType::Credit,
        ]);

        Transaction::factory()->count(2)->create([
            'to_wallet_id' => $this->wallet->id,
            'type' => TransactionType::Debit,
        ]);

        // Filter by Credit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?type=credit&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('credit', $transaction['type']);
        }

        // Filter by Debit
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?type=debit&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('debit', $transaction['type']);
        }
    }

    public function test_can_filter_transactions_by_date_from(): void
    {
        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-02-01 10:00:00',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?date_from=2025-01-15&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2025-02-01 10:00:00', Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_date_to(): void
    {
        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-02-01 10:00:00',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?date_to=2025-01-15&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2025-01-01 10:00:00', Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_date_range(): void
    {
        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-01-15 10:00:00',
        ]);

        Transaction::factory()->create([
            'to_wallet_id' => $this->wallet->id,
            'created_at' => '2025-02-01 10:00:00',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?date_from=2025-01-10&date_to=2025-01-20&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2025-01-15 10:00:00', Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }

    public function test_can_filter_transactions_by_amount_range(): void
    {
        Transaction::factory()->create(['to_wallet_id' => $this->wallet->id, 'amount' => 50]);
        Transaction::factory()->create(['to_wallet_id' => $this->wallet->id, 'amount' => 150]);
        Transaction::factory()->create(['to_wallet_id' => $this->wallet->id, 'amount' => 250]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?amount_min=100&amount_max=200&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals(150, (float) Transaction::find($response->json('data.0.id'))->amount);
    }

    public function test_can_filter_transactions_by_reference(): void
    {
        Transaction::factory()->create(['to_wallet_id' => $this->wallet->id, 'reference' => 'Invoice 123']);
        Transaction::factory()->create(['to_wallet_id' => $this->wallet->id, 'reference' => 'Refund 456']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?reference=Refund&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertStringContainsString('Refund', Transaction::find($response->json('data.0.id'))->reference);
    }

    public function test_can_filter_transactions_by_wallets(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);
        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id]);

        Transaction::factory()->create(['from_wallet_id' => $wallet1->id, 'to_wallet_id' => $wallet2->id]);
        Transaction::factory()->create(['from_wallet_id' => $wallet2->id, 'to_wallet_id' => $wallet1->id]);

        // Filter by from_wallet_id
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?from_wallet_id={$wallet1->id}&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($wallet1->id, $response->json('data.0.from_wallet_id'));

        // Filter by to_wallet_id
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?to_wallet_id={$wallet1->id}&company_id={$this->company->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($wallet1->id, $response->json('data.0.to_wallet_id'));
    }

    public function test_guests_are_denied_access_to_transactions(): void
    {
        $response = $this->getJson('/api/v0/transactions');

        $response->assertUnauthorized();
    }

    public function test_transactions_are_paginated(): void
    {
        Transaction::factory()->count(15)->create([
            'to_wallet_id' => $this->wallet->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v0/transactions?per_page=5&company_id={$this->company->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15);
    }
}
