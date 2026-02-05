<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
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
        $response = $this->actingAs($this->user)
            ->getJson('/api/v0/transactions?type=credit');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('credit', $transaction['type']);
        }

        // Filter by Debit
        $response = $this->actingAs($this->user)
            ->getJson('/api/v0/transactions?type=debit');

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

        $response = $this->actingAs($this->user)
            ->getJson('/api/v0/transactions?date_from=2025-01-15');

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

        $response = $this->actingAs($this->user)
            ->getJson('/api/v0/transactions?date_to=2025-01-15');

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

        $response = $this->actingAs($this->user)
            ->getJson('/api/v0/transactions?date_from=2025-01-10&date_to=2025-01-20');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('2025-01-15 10:00:00', Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString());
    }
}
