<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TransferService
{
    /**
     * Initiate a transfer (internal or external).
     *
     * All operations are wrapped in a DB transaction with row-level locking
     * on the sender wallet to prevent double-spending.
     *
     * @param  array<string, mixed>  $data
     * @return array{group_id: string, status: string}
     */
    public function initiateTransfer(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = (float) $data['amount'];
            $isExternal = (bool) $data['external'];

            // Lock sender wallet for update to prevent concurrent balance manipulation
            $senderWallet = Wallet::lockForUpdate()->findOrFail($data['sender_wallet_id']);

            // Pre-flight: available funds check
            $availableBalance = (float) $senderWallet->balance - (float) $senderWallet->locked_balance;
            if ($availableBalance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient available funds.'],
                ]);
            }

            $receiverWallet = null;
            if (! $isExternal) {
                $receiverWallet = Wallet::findOrFail($data['receiver_wallet_id']);

                // Pre-flight: currency barrier
                if ($senderWallet->currency !== $receiverWallet->currency) {
                    throw ValidationException::withMessages([
                        'receiver_wallet_id' => ['Cross-currency transfers are not permitted at this stage.'],
                    ]);
                }
            }

            // Determine target status (auto-approve logic)
            $targetStatus = $this->determineTargetStatus($user, $amount, $senderWallet->currency->value);

            $groupId = Str::uuid()->toString();
            $currency = $senderWallet->currency->value;

            if ($targetStatus === TransactionStatus::PendingApproval) {
                $this->executePendingTransfer(
                    $senderWallet, $receiverWallet, $groupId, $amount,
                    $currency, $user, $isExternal, $data
                );
            } else {
                $this->executeCompletedTransfer(
                    $senderWallet, $receiverWallet, $groupId, $amount,
                    $currency, $user, $isExternal, $data
                );
            }

            return [
                'group_id' => $groupId,
                'status' => $targetStatus->value,
            ];
        });
    }

    /**
     * Review (approve or reject) a pending transfer.
     *
     * Uses optimistic locking via the status column to prevent double-approvals.
     *
     * @return array{group_id: string, status: string}
     */
    public function reviewTransfer(User $reviewer, string $groupId, string $action, ?string $reason): array
    {
        return DB::transaction(function () use ($reviewer, $groupId, $action, $reason) {
            // Optimistic lock: only update rows still in pending_approval status
            $affectedRows = Transaction::where('group_id', $groupId)
                ->where('status', TransactionStatus::PendingApproval)
                ->update([
                    'status' => $action === 'approve'
                        ? TransactionStatus::Completed
                        : TransactionStatus::Rejected,
                    'reviewer_user_id' => $reviewer->id,
                    'reject_reason' => $action === 'reject' ? $reason : null,
                ]);

            if ($affectedRows === 0) {
                throw new ConflictHttpException('This transaction has already been reviewed.');
            }

            // Get the transactions for balance updates
            $transactions = Transaction::where('group_id', $groupId)->get();
            $debitTx = $transactions->firstWhere('type', TransactionType::Debit);

            if (! $debitTx) {
                throw new ConflictHttpException('Invalid transaction group.');
            }

            $senderWallet = Wallet::lockForUpdate()->findOrFail($debitTx->wallet_id);
            $amount = abs((float) $debitTx->amount);

            if ($action === 'approve') {
                // Release locked funds (balance auto-adjusts via completed transaction sums)
                $senderWallet->decrement('locked_balance', $amount);
                // Reload to get fresh computed balance, then deduct via transaction sum
                // The balance is computed from completed transactions, so setting status
                // to completed above already updates the computed balance automatically.
                // We just need to release the lock.

                // For internal transfers, receiver balance also updates automatically
                // because the credit row's status is now 'completed'.
            } else {
                // Reject: just release the locked funds
                $senderWallet->decrement('locked_balance', $amount);
            }

            return [
                'group_id' => $groupId,
                'status' => $action === 'approve'
                    ? TransactionStatus::Completed->value
                    : TransactionStatus::Rejected->value,
            ];
        });
    }

    /**
     * Determine the target status based on role and threshold.
     */
    private function determineTargetStatus(User $user, float $amount, string $currency): TransactionStatus
    {
        // Admins and Managers auto-approve
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return TransactionStatus::Completed;
        }

        // Members: check threshold
        $thresholds = config('transactions.approval_thresholds', []);
        $threshold = $thresholds[$currency] ?? 0;

        if ($amount > $threshold) {
            return TransactionStatus::PendingApproval;
        }

        return TransactionStatus::Completed;
    }

    /**
     * Execute a pending transfer — lock funds in escrow.
     *
     * @param  array<string, mixed>  $data
     */
    private function executePendingTransfer(
        Wallet $senderWallet,
        ?Wallet $receiverWallet,
        string $groupId,
        float $amount,
        string $currency,
        User $user,
        bool $isExternal,
        array $data
    ): void {
        // Lock funds in escrow
        $senderWallet->increment('locked_balance', $amount);

        // Debit row for sender
        Transaction::create([
            'group_id' => $groupId,
            'wallet_id' => $senderWallet->id,
            'counterpart_wallet_id' => $receiverWallet?->id,
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'external' => $isExternal,
            'reference' => $data['reference'] ?? null,
            'status' => TransactionStatus::PendingApproval,
            'currency' => $currency,
            'exchange_rate' => 1.0,
            'initiator_user_id' => $user->id,
            'external_address' => $isExternal ? ($data['external_address'] ?? null) : null,
            'external_name' => $isExternal ? ($data['external_name'] ?? null) : null,
        ]);

        // Credit row for receiver (internal only)
        if (! $isExternal && $receiverWallet) {
            Transaction::create([
                'group_id' => $groupId,
                'wallet_id' => $receiverWallet->id,
                'counterpart_wallet_id' => $senderWallet->id,
                'type' => TransactionType::Credit,
                'amount' => $amount,
                'external' => false,
                'reference' => $data['reference'] ?? null,
                'status' => TransactionStatus::PendingApproval,
                'currency' => $currency,
                'exchange_rate' => 1.0,
                'initiator_user_id' => $user->id,
            ]);
        }
    }

    /**
     * Execute a completed transfer — immediately move funds.
     *
     * @param  array<string, mixed>  $data
     */
    private function executeCompletedTransfer(
        Wallet $senderWallet,
        ?Wallet $receiverWallet,
        string $groupId,
        float $amount,
        string $currency,
        User $user,
        bool $isExternal,
        array $data
    ): void {
        // Balance is computed from completed transaction sums, so inserting
        // completed transaction rows automatically adjusts the computed balance.
        // No need to manually update a stored balance column.

        // Debit row for sender
        Transaction::create([
            'group_id' => $groupId,
            'wallet_id' => $senderWallet->id,
            'counterpart_wallet_id' => $receiverWallet?->id,
            'type' => TransactionType::Debit,
            'amount' => -$amount,
            'external' => $isExternal,
            'reference' => $data['reference'] ?? null,
            'status' => TransactionStatus::Completed,
            'currency' => $currency,
            'exchange_rate' => 1.0,
            'initiator_user_id' => $user->id,
            'reviewer_user_id' => $user->id,
            'external_address' => $isExternal ? ($data['external_address'] ?? null) : null,
            'external_name' => $isExternal ? ($data['external_name'] ?? null) : null,
        ]);

        // Credit row for receiver (internal only)
        if (! $isExternal && $receiverWallet) {
            Transaction::create([
                'group_id' => $groupId,
                'wallet_id' => $receiverWallet->id,
                'counterpart_wallet_id' => $senderWallet->id,
                'type' => TransactionType::Credit,
                'amount' => $amount,
                'external' => false,
                'reference' => $data['reference'] ?? null,
                'status' => TransactionStatus::Completed,
                'currency' => $currency,
                'exchange_rate' => 1.0,
                'initiator_user_id' => $user->id,
                'reviewer_user_id' => $user->id,
            ]);
        }
    }
}
