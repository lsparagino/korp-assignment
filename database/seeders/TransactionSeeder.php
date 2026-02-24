<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
        // Track how many logical transactions each wallet has originated.
        $txCounts = $wallets->pluck('id')->mapWithKeys(fn ($id) => [$id => 0])->all();

        foreach ($wallets as $wallet) {
            if ($txCounts[$wallet->id] >= self::MAX_TRANSACTIONS_PER_WALLET) {
                continue;
            }

            // --- 1. External inbound credit to fund the wallet ---
            $creditAmount = collect(self::AMOUNTS)->random();
            $this->createExternalCredit($wallet->id, $creditAmount);
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

            // --- 3. Another external inbound credit ---
            if ($txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $creditAmount = collect(self::AMOUNTS)->random();
                $this->createExternalCredit($wallet->id, $creditAmount);
                $balances[$wallet->id] += $creditAmount;
                $txCounts[$wallet->id]++;
            }

            // --- 4. External outbound debit ---
            if ($txCounts[$wallet->id] < self::MAX_TRANSACTIONS_PER_WALLET) {
                $debitAmount = $this->pickSafeAmount($balances[$wallet->id]);

                if ($debitAmount !== null) {
                    $this->createExternalDebit($wallet->id, $debitAmount);
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
     */
    private function pickSafeAmount(float $balance): ?float
    {
        $safe = collect(self::AMOUNTS)->filter(fn ($a) => $a <= $balance);

        return $safe->isNotEmpty() ? $safe->random() : null;
    }

    /**
     * Create an external inbound credit (deposit).
     */
    private function createExternalCredit(int $walletId, float $amount): void
    {
        Transaction::factory()->create([
            'wallet_id' => $walletId,
            'counterpart_wallet_id' => null,
            'type' => TransactionType::Credit,
            'amount' => $amount,
            'external' => true,
        ]);
    }

    /**
     * Create an external outbound debit (withdrawal).
     */
    private function createExternalDebit(int $walletId, float $amount): void
    {
        Transaction::factory()->create([
            'wallet_id' => $walletId,
            'counterpart_wallet_id' => null,
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'external' => true,
        ]);
    }

    /**
     * Create a paired internal transfer (two entries with the same group_id).
     */
    private function createInternalTransfer(int $fromWalletId, int $toWalletId, float $amount): void
    {
        $groupId = Str::uuid()->toString();
        $date = fake()->dateTimeBetween('-1 year', 'now');
        $reference = fake()->sentence(4);

        // Debit entry for the sender
        Transaction::factory()->create([
            'group_id' => $groupId,
            'wallet_id' => $fromWalletId,
            'counterpart_wallet_id' => $toWalletId,
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'external' => false,
            'reference' => $reference,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // Credit entry for the receiver
        Transaction::factory()->create([
            'group_id' => $groupId,
            'wallet_id' => $toWalletId,
            'counterpart_wallet_id' => $fromWalletId,
            'type' => TransactionType::Credit,
            'amount' => $amount,
            'external' => false,
            'reference' => $reference,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
