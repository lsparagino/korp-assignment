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
    public function initiateTransfer(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $amount = (float) $data['amount'];
            $isExternal = (bool) $data['external'];

            $senderWallet = Wallet::lockForUpdate()->findOrFail($data['sender_wallet_id']);

            $availableBalance = (float) $senderWallet->balance - (float) $senderWallet->locked_balance;
            if ($availableBalance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient available funds.'],
                ]);
            }

            $receiverWallet = null;
            if (! $isExternal) {
                $receiverWallet = Wallet::findOrFail($data['receiver_wallet_id']);

                if ($senderWallet->currency !== $receiverWallet->currency) {
                    throw ValidationException::withMessages([
                        'receiver_wallet_id' => ['Cross-currency transfers are not permitted at this stage.'],
                    ]);
                }
            }

            $status = $this->determineTargetStatus($user, $amount, $senderWallet->currency->value);
            $groupId = Str::uuid()->toString();

            if ($status === TransactionStatus::PendingApproval) {
                $senderWallet->increment('locked_balance', $amount);
            }

            $builder = new TransactionBuilder(
                $groupId,
                $senderWallet->currency->value,
                $status,
                $user,
                $data,
            );

            $builder->debit($senderWallet, $receiverWallet, $amount, $isExternal, $data);

            if (! $isExternal && $receiverWallet) {
                $builder->credit($receiverWallet, $senderWallet, $amount);
            }

            return [
                'group_id' => $groupId,
                'status' => $status->value,
            ];
        });
    }

    public function reviewTransfer(User $reviewer, string $groupId, string $action, ?string $reason): array
    {
        return DB::transaction(function () use ($reviewer, $groupId, $action, $reason) {
            $newStatus = $action === 'approve'
                ? TransactionStatus::Completed
                : TransactionStatus::Rejected;

            $affectedRows = Transaction::where('group_id', $groupId)
                ->where('status', TransactionStatus::PendingApproval)
                ->update([
                    'status' => $newStatus,
                    'reviewer_user_id' => $reviewer->id,
                    'reject_reason' => $action === 'reject' ? $reason : null,
                ]);

            if ($affectedRows === 0) {
                throw new ConflictHttpException('This transaction has already been reviewed.');
            }

            $debitTransaction = Transaction::where('group_id', $groupId)
                ->where('type', TransactionType::Debit)
                ->firstOrFail();

            $senderWallet = Wallet::lockForUpdate()->findOrFail($debitTransaction->wallet_id);
            $amount = abs((float) $debitTransaction->amount);

            $senderWallet->decrement('locked_balance', $amount);

            return [
                'group_id' => $groupId,
                'status' => $newStatus->value,
            ];
        });
    }

    private function determineTargetStatus(User $user, float $amount, string $currency): TransactionStatus
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return TransactionStatus::Completed;
        }

        $threshold = config("transactions.approval_thresholds.{$currency}", 0);

        return $amount > $threshold
            ? TransactionStatus::PendingApproval
            : TransactionStatus::Completed;
    }
}
