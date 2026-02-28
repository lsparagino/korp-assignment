<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\CompanySetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionCompleted;
use App\Notifications\TransactionPendingApproval;
use App\Notifications\TransactionRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TransferService
{
    public function initiateTransfer(User $user, array $data): array
    {
        $this->enforceDailyLimit($user, (float) $data['amount']);

        $result = DB::transaction(function () use ($user, $data) {
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

            $status = $this->determineTargetStatus($user, $amount, $senderWallet->currency->value, $senderWallet->company_id);
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
                'company_id' => $senderWallet->company_id,
            ];
        });

        $this->sendInitiateNotifications($user, $result);

        return $result;
    }

    public function reviewTransfer(User $reviewer, string $groupId, string $action, ?string $reason): array
    {
        $result = DB::transaction(function () use ($reviewer, $groupId, $action, $reason) {
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

        $this->sendReviewNotifications($groupId, $action);

        return $result;
    }

    private function determineTargetStatus(User $user, float $amount, string $currency, int $companyId): TransactionStatus
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return TransactionStatus::Completed;
        }

        $setting = CompanySetting::where('company_id', $companyId)
            ->where('currency', $currency)
            ->first();

        if (! $setting) {
            return TransactionStatus::Completed;
        }

        return $amount > (float) $setting->approval_threshold
            ? TransactionStatus::PendingApproval
            : TransactionStatus::Completed;
    }

    private function sendInitiateNotifications(User $initiator, array $result): void
    {
        $debitTransaction = Transaction::where('group_id', $result['group_id'])
            ->where('type', TransactionType::Debit)
            ->with(['wallet', 'initiator'])
            ->first();

        if (! $debitTransaction) {
            return;
        }

        if ($result['status'] === TransactionStatus::Completed->value) {
            $initiator->notify(new TransactionCompleted($debitTransaction));
        }

        if ($result['status'] === TransactionStatus::PendingApproval->value) {
            $approvers = User::forCompany($result['company_id'])
                ->whereIn('role', [UserRole::Admin, UserRole::Manager])
                ->with('setting')
                ->get();

            Notification::send($approvers, new TransactionPendingApproval($debitTransaction));
        }
    }

    private function sendReviewNotifications(string $groupId, string $action): void
    {
        $debitTransaction = Transaction::where('group_id', $groupId)
            ->where('type', TransactionType::Debit)
            ->with(['wallet', 'initiator', 'reviewer'])
            ->first();

        if (! $debitTransaction || ! $debitTransaction->initiator) {
            return;
        }

        $notification = $action === 'approve'
            ? new TransactionApproved($debitTransaction)
            : new TransactionRejected($debitTransaction);

        $debitTransaction->initiator->notify($notification);
    }

    private function enforceDailyLimit(User $user, float $amount): void
    {
        $setting = $user->setting;

        if (! $setting || $setting->daily_transaction_limit === null) {
            return;
        }

        $todayTotal = Transaction::where('initiator_user_id', $user->id)
            ->where('type', TransactionType::Debit)
            ->whereIn('status', [
                TransactionStatus::Completed,
                TransactionStatus::PendingApproval,
            ])
            ->whereDate('created_at', today())
            ->sum(DB::raw('ABS(amount)'));

        if (((float) $todayTotal + $amount) > (float) $setting->daily_transaction_limit) {
            throw ValidationException::withMessages([
                'amount' => ['This transfer would exceed your daily transaction limit.'],
            ]);
        }
    }
}
