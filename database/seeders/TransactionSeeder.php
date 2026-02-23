<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /** Amounts that are easily verified by a human. */
    private const AMOUNTS = [10, 100, 1000, 10.50, 100.50];

    private const MAX_TRANSACTIONS_PER_WALLET = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        if ($wallets->count() < 2) {
            return;
        }

        // Track the running balance for every wallet so we never go negative.
        $balances = $wallets->pluck('id')->mapWithKeys(fn ($id) => [$id => 0])->all();
        // Track how many transactions each wallet has participated in (as sender).
        $txCounts = $wallets->pluck('id')->mapWithKeys(fn ($id) => [$id => 0])->all();

        foreach ($wallets as $wallet) {
            if ($txCounts[$wallet->id] >= self::MAX_TRANSACTIONS_PER_WALLET) {
                continue;
            }

            // --- 1. Seed an inbound external credit first to fund the wallet ---
            $creditAmount = collect(self::AMOUNTS)->random();
            $this->createTransaction(
                type: TransactionType::Credit,
                amount: $creditAmount,
                fromWalletId: null,
                toWalletId: $wallet->id,
                external: true,
            );
            $balances[$wallet->id] += $creditAmount;
            $txCounts[$wallet->id]++;

            // --- 2. Internal transfer to a matching-currency wallet ---
            $matchingWallets = $wallets->where('currency', $wallet->currency)
                ->where('id', '!=', $wallet->id);

            if ($matchingWallets->isNotEmpty() && $txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $debitAmount = $this->pickSafeAmount($balances[$wallet->id]);

                if ($debitAmount !== null) {
                    $targetWallet = $matchingWallets->random();

                    $this->createInternalTransfer($wallet->id, $targetWallet->id, $debitAmount);
                    $balances[$wallet->id] -= $debitAmount;
                    $balances[$targetWallet->id] += $debitAmount;
                    $txCounts[$wallet->id]++;
                }
            }

            // --- 3. Another inbound credit ---
            if ($txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $creditAmount = collect(self::AMOUNTS)->random();
                $this->createTransaction(
                    type: TransactionType::Credit,
                    amount: $creditAmount,
                    fromWalletId: null,
                    toWalletId: $wallet->id,
                    external: true,
                );
                $balances[$wallet->id] += $creditAmount;
                $txCounts[$wallet->id]++;
            }

            // --- 4. External outbound debit ---
            if ($txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $debitAmount = $this->pickSafeAmount($balances[$wallet->id]);

                if ($debitAmount !== null) {
                    $this->createTransaction(
                        type: TransactionType::Debit,
                        amount: $debitAmount,
                        fromWalletId: $wallet->id,
                        toWalletId: null,
                        external: true,
                    );
                    $balances[$wallet->id] -= $debitAmount;
                    $txCounts[$wallet->id]++;
                }
            }

            // --- 5. One more internal transfer if possible ---
            if ($matchingWallets->isNotEmpty() && $txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $debitAmount = $this->pickSafeAmount($balances[$wallet->id]);

                if ($debitAmount !== null) {
                    $targetWallet = $matchingWallets->random();

                    $this->createInternalTransfer($wallet->id, $targetWallet->id, $debitAmount);
                    $balances[$wallet->id] -= $debitAmount;
                    $balances[$targetWallet->id] += $debitAmount;
                    $txCounts[$wallet->id]++;
                }
            }
        }
    }

    /**
     * Pick a random human-friendly amount that won't cause the balance to go negative.
     * Returns null if no amount is safe.
     */
    private function pickSafeAmount(float $balance): ?float
    {
        $safe = collect(self::AMOUNTS)->filter(fn ($a) => $a <= $balance);

        return $safe->isNotEmpty() ? $safe->random() : null;
    }

    /**
     * Create a single transaction record.
     */
    private function createTransaction(
        TransactionType $type,
        float $amount,
        ?int $fromWalletId,
        ?int $toWalletId,
        bool $external,
    ): void {
        Transaction::factory()->create([
            'type' => $type,
            'amount' => $type === TransactionType::Debit ? -$amount : $amount,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'external' => $external,
        ]);
    }

    /**
     * Create a pair of transactions for an internal transfer.
     *
     * The wallet balance is computed as:
     *   sum(toTransactions.amount) + sum(fromTransactions.amount)
     *
     * So the sender needs a negative-amount debit in fromTransactions,
     * and the receiver needs a positive-amount credit in toTransactions.
     */
    private function createInternalTransfer(int $fromWalletId, int $toWalletId, float $amount): void
    {
        $date = fake()->dateTimeBetween('-1 year', 'now');
        $reference = fake()->sentence(4);

        // Debit from sender (only from_wallet_id so it only affects sender's balance)
        Transaction::factory()->create([
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => null,
            'external' => false,
            'reference' => $reference,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Credit to receiver (only to_wallet_id so it only affects receiver's balance)
        Transaction::factory()->create([
            'type' => TransactionType::Credit,
            'amount' => $amount,
            'from_wallet_id' => null,
            'to_wallet_id' => $toWalletId,
            'external' => false,
            'reference' => $reference,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
