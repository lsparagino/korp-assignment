<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionFilterTest extends TestCase
{
    use RefreshDatabase;

    private const TRANSACTIONS_ENDPOINT = '/api/v0/transactions';

    private const BASE_DATE = '2025-01-01 10:00:00';

    private const SECOND_DATE = '2025-02-01 10:00:00';

    private const MID_DATE = '2025-01-15 10:00:00';

    private const MID_DATE_ONLY = '2025-01-15';

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

    /**
     * Make an authenticated GET request to the transactions endpoint.
     *
     * @param  array<string, mixed>  $filters
     */
    private function fetchTransactions(array $filters = []): \Illuminate\Testing\TestResponse
    {
        $filters['company_id'] = $this->company->id;
        $query = http_build_query($filters);

        return $this->actingAs($this->user, 'sanctum')
            ->getJson(self::TRANSACTIONS_ENDPOINT."?{$query}");
    }

    /**
     * Create a transaction on the default wallet.
     *
     * @param  array<string, mixed>  $attrs
     */
    private function createTransaction(array $attrs = []): Transaction
    {
        return Transaction::factory()->create(array_merge(
            ['wallet_id' => $this->wallet->id],
            $attrs,
        ));
    }

    /**
     * Create an internal transfer pair (debit + credit) between two wallets.
     *
     * @return string The group_id
     */
    private function createTransferPair(Wallet $from, Wallet $to, float $amount): string
    {
        $groupId = Str::uuid()->toString();

        Transaction::factory()->create([
            'group_id' => $groupId,
            'wallet_id' => $from->id,
            'counterpart_wallet_id' => $to->id,
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'external' => false,
        ]);

        Transaction::factory()->create([
            'group_id' => $groupId,
            'wallet_id' => $to->id,
            'counterpart_wallet_id' => $from->id,
            'type' => TransactionType::Credit,
            'amount' => $amount,
            'external' => false,
        ]);

        return $groupId;
    }

    /**
     * Create an external transaction (no counterpart).
     */
    private function createExternalTransaction(Wallet $wallet, TransactionType $type, float $amount): Transaction
    {
        return Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'counterpart_wallet_id' => null,
            'type' => $type,
            'amount' => $amount,
            'external' => true,
        ]);
    }

    /**
     * Create a wallet for the default user within the default company.
     */
    private function createWallet(): Wallet
    {
        return Wallet::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);
    }

    // ── Type Filter ──────────────────────────────────────────────────

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

        $response = $this->fetchTransactions(['type' => 'credit']);
        $response->assertOk()->assertJsonCount(3, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('credit', $transaction['type']);
        }

        $response = $this->fetchTransactions(['type' => 'debit']);
        $response->assertOk()->assertJsonCount(2, 'data');
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals('debit', $transaction['type']);
        }
    }

    // ── Date Filters ─────────────────────────────────────────────────

    public function test_can_filter_transactions_by_date_from(): void
    {
        $this->createTransaction(['created_at' => self::BASE_DATE]);
        $this->createTransaction(['created_at' => self::SECOND_DATE]);

        $response = $this->fetchTransactions(['date_from' => self::MID_DATE_ONLY, 'tz' => 'UTC']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(
            self::SECOND_DATE,
            Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString(),
        );
    }

    public function test_can_filter_transactions_by_date_to(): void
    {
        $this->createTransaction(['created_at' => self::BASE_DATE]);
        $this->createTransaction(['created_at' => self::SECOND_DATE]);

        $response = $this->fetchTransactions(['date_to' => self::MID_DATE_ONLY, 'tz' => 'UTC']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(
            self::BASE_DATE,
            Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString(),
        );
    }

    public function test_can_filter_transactions_by_date_range(): void
    {
        $this->createTransaction(['created_at' => self::BASE_DATE]);
        $this->createTransaction(['created_at' => self::MID_DATE]);
        $this->createTransaction(['created_at' => self::SECOND_DATE]);

        $response = $this->fetchTransactions(['date_from' => '2025-01-10', 'date_to' => '2025-01-20', 'tz' => 'UTC']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(
            self::MID_DATE,
            Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString(),
        );
    }

    public function test_date_from_filter_converts_timezone_to_utc(): void
    {
        // 2025-01-15 02:00:00 UTC — this is before midnight EST (05:00 UTC)
        $this->createTransaction(['created_at' => '2025-01-15 02:00:00']);
        // 2025-01-15 06:00:00 UTC — this is after midnight EST (05:00 UTC)
        $this->createTransaction(['created_at' => '2025-01-15 06:00:00']);

        // date_from=2025-01-15 in America/New_York means >= 2025-01-15 05:00:00 UTC
        $response = $this->fetchTransactions(['date_from' => self::MID_DATE_ONLY, 'tz' => 'America/New_York']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(
            '2025-01-15 06:00:00',
            Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString(),
        );
    }

    public function test_date_to_filter_converts_timezone_to_utc(): void
    {
        // 2025-01-15 23:30:00 UTC — within Jan 15 end-of-day EST (next day 04:59:59 UTC)
        $this->createTransaction(['created_at' => '2025-01-15 23:30:00']);
        // 2025-01-16 06:00:00 UTC — after Jan 15 end-of-day EST
        $this->createTransaction(['created_at' => '2025-01-16 06:00:00']);

        // date_to=2025-01-15 in America/New_York means <= 2025-01-16 04:59:59 UTC
        $response = $this->fetchTransactions(['date_to' => self::MID_DATE_ONLY, 'tz' => 'America/New_York']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(
            '2025-01-15 23:30:00',
            Transaction::find($response->json('data.0.id'))->created_at->toDateTimeString(),
        );
    }

    public function test_tz_is_required_when_date_filter_is_present(): void
    {
        $response = $this->fetchTransactions(['date_from' => self::MID_DATE_ONLY]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['tz']);
    }

    public function test_tz_is_not_required_when_no_dates(): void
    {
        $this->createTransaction();

        $response = $this->fetchTransactions(['type' => 'credit']);

        $response->assertOk();
    }

    // ── Amount & Reference Filters ───────────────────────────────────

    public function test_can_filter_transactions_by_amount_range(): void
    {
        $this->createTransaction(['amount' => 50]);
        $this->createTransaction(['amount' => 150]);
        $this->createTransaction(['amount' => 250]);

        $response = $this->fetchTransactions(['amount_min' => 100, 'amount_max' => 200]);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals(150, (float) Transaction::find($response->json('data.0.id'))->amount);
    }

    public function test_can_filter_transactions_by_reference(): void
    {
        $this->createTransaction(['reference' => 'Invoice 123']);
        $this->createTransaction(['reference' => 'Refund 456']);

        $response = $this->fetchTransactions(['reference' => 'Refund']);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringContainsString('Refund', Transaction::find($response->json('data.0.id'))->reference);
    }

    // ── Wallet Direction Filters ─────────────────────────────────────

    public function test_from_wallet_filter_returns_transactions_where_wallet_is_source(): void
    {
        $walletA = $this->createWallet();
        $walletB = $this->createWallet();
        $walletC = $this->createWallet();

        // A→B transfer
        $this->createTransferPair($walletA, $walletB, 100);
        // C→A transfer
        $this->createTransferPair($walletC, $walletA, 50);

        // from_wallet_id = walletA → only the A→B debit
        $response = $this->fetchTransactions(['from_wallet_id' => $walletA->id]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_to_wallet_filter_returns_transactions_where_wallet_is_destination(): void
    {
        $walletA = $this->createWallet();
        $walletB = $this->createWallet();

        $this->createTransferPair($walletA, $walletB, 100);
        $this->createExternalTransaction($walletA, TransactionType::Credit, 200);

        // to_wallet_id = walletB → only the A→B debit entry
        $response = $this->fetchTransactions(['to_wallet_id' => $walletB->id]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_has_wallet_filter_returns_transactions_involving_wallet(): void
    {
        $walletA = $this->createWallet();
        $walletB = $this->createWallet();

        $this->createExternalTransaction($walletA, TransactionType::Credit, 200);
        $this->createExternalTransaction($walletB, TransactionType::Credit, 300);
        $this->createTransferPair($walletA, $walletB, 50);

        // has_wallet_id = walletA → external credit + debit A→B = 2
        $response = $this->fetchTransactions(['has_wallet_id' => $walletA->id]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    // ── External Wallet Filters ──────────────────────────────────────

    public function test_from_external_filter_returns_only_credits_from_outside(): void
    {
        $walletA = $this->createWallet();

        $externalCredit = $this->createExternalTransaction($walletA, TransactionType::Credit, 200);
        $this->createExternalTransaction($walletA, TransactionType::Debit, -150);

        $response = $this->fetchTransactions(['from_wallet_id' => 'external']);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($externalCredit->id, $response->json('data.0.id'));
    }

    public function test_to_external_filter_returns_only_debits_sent_outside(): void
    {
        $walletA = $this->createWallet();

        $this->createExternalTransaction($walletA, TransactionType::Credit, 200);
        $externalDebit = $this->createExternalTransaction($walletA, TransactionType::Debit, -150);

        $response = $this->fetchTransactions(['to_wallet_id' => 'external']);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($externalDebit->id, $response->json('data.0.id'));
    }

    public function test_has_wallet_external_filter_returns_all_external_transactions(): void
    {
        $walletA = $this->createWallet();
        $walletB = $this->createWallet();

        $this->createExternalTransaction($walletA, TransactionType::Credit, 200);
        $this->createExternalTransaction($walletA, TransactionType::Debit, -100);

        // Internal transfer — should NOT match
        Transaction::factory()->create([
            'wallet_id' => $walletA->id,
            'counterpart_wallet_id' => $walletB->id,
            'type' => TransactionType::Debit,
            'amount' => -50,
            'external' => false,
        ]);

        $response = $this->fetchTransactions(['has_wallet_id' => 'external']);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    // ── Auth & Pagination ────────────────────────────────────────────

    public function test_guests_are_denied_access_to_transactions(): void
    {
        $this->getJson(self::TRANSACTIONS_ENDPOINT)->assertUnauthorized();
    }

    public function test_transactions_are_paginated(): void
    {
        Transaction::factory()->count(15)->create([
            'wallet_id' => $this->wallet->id,
        ]);

        $response = $this->fetchTransactions(['per_page' => 5]);

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15);
    }

    // ── Deduplication ────────────────────────────────────────────────

    public function test_internal_transfers_are_deduplicated(): void
    {
        $wallet1 = $this->createWallet();
        $wallet2 = $this->createWallet();

        $this->createTransferPair($wallet1, $wallet2, 100);

        $response = $this->fetchTransactions();

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('debit', $response->json('data.0.type'));
    }

    // ── Status Filters ───────────────────────────────────────────────

    #[DataProvider('statusFilterProvider')]
    public function test_can_filter_transactions_by_status(string $filterValue, TransactionStatus $expected, array $others): void
    {
        $this->createTransaction(['status' => $expected]);
        foreach ($others as $status) {
            $this->createTransaction(['status' => $status]);
        }

        $response = $this->fetchTransactions(['status' => $filterValue]);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($filterValue, $response->json('data.0.status'));
    }

    /**
     * @return array<string, array{string, TransactionStatus, TransactionStatus[]}>
     */
    public static function statusFilterProvider(): array
    {
        return [
            'completed' => ['completed', TransactionStatus::Completed, [TransactionStatus::PendingApproval, TransactionStatus::Rejected]],
            'pending_approval' => ['pending_approval', TransactionStatus::PendingApproval, [TransactionStatus::Completed]],
            'rejected' => ['rejected', TransactionStatus::Rejected, [TransactionStatus::Completed]],
        ];
    }

    // ── Initiator Filter ─────────────────────────────────────────────

    public function test_can_filter_transactions_by_initiator_user_id(): void
    {
        $initiator = User::factory()->create();
        $initiator->companies()->attach($this->company);

        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->company);

        $this->createTransaction(['initiator_user_id' => $initiator->id]);
        $this->createTransaction(['initiator_user_id' => $otherUser->id]);
        $this->createTransaction(['initiator_user_id' => null]);

        $response = $this->fetchTransactions(['initiator_user_id' => $initiator->id]);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($initiator->id, $response->json('data.0.initiator_user_id'));
    }
}
