<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
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
    public function __construct(private AuditService $auditService) {}

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
                    'amount' => [__('messages.insufficient_funds')],
                ]);
            }

            $receiverWallet = null;
            if (! $isExternal) {
                $receiverWallet = Wallet::findOrFail($data['receiver_wallet_id']);

                if ($senderWallet->currency !== $receiverWallet->currency) {
                    throw ValidationException::withMessages([
                        'receiver_wallet_id' => [__('messages.cross_currency_not_permitted')],
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
                'currency' => $senderWallet->currency->value,
                'sender_wallet_name' => $senderWallet->name,
                'receiver_wallet_name' => $receiverWallet?->name,
            ];
        });

        $this->sendInitiateNotifications($user, $result);

        $this->auditService->log(
            AuditCategory::Transaction,
            AuditSeverity::Normal,
            'transfer.initiated',
            __('messages.audit.transfer_initiated'),
            ['metadata' => [
                'group_id' => $result['group_id'],
                'amount' => $data['amount'],
                'currency' => $result['currency'],
                'sender_wallet' => $result['sender_wallet_name'],
                'receiver_wallet' => $result['receiver_wallet_name'],
                'external' => $data['external'] ?? false,
                'status' => $result['status'],
            ]],
        );

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
                throw new ConflictHttpException(__('messages.transfer_already_reviewed'));
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

        $auditAction = $action === 'approve' ? 'transfer.approved' : 'transfer.rejected';
        $auditMessage = $action === 'approve'
            ? __('messages.audit.transfer_approved')
            : __('messages.audit.transfer_rejected');

        $debitTx = Transaction::where('group_id', $groupId)
            ->where('type', TransactionType::Debit)
            ->with('wallet')
            ->first();

        $this->auditService->log(
            AuditCategory::Transaction,
            AuditSeverity::Normal,
            $auditAction,
            $auditMessage,
            ['metadata' => [
                'group_id' => $groupId,
                'action' => $action,
                'amount' => $debitTx ? abs((float) $debitTx->amount) : null,
                'currency' => $debitTx?->currency,
                'wallet_name' => $debitTx?->wallet?->name,
                'reason' => $reason,
            ]],
        );

        return $result;
    }

    public function cancelTransfer(User $initiator, string $groupId): array
    {
        $result = DB::transaction(function () use ($initiator, $groupId) {
            $affectedRows = Transaction::where('group_id', $groupId)
                ->where('initiator_user_id', $initiator->id)
                ->where('status', TransactionStatus::PendingApproval)
                ->update(['status' => TransactionStatus::Cancelled]);

            if ($affectedRows === 0) {
                throw new ConflictHttpException(__('messages.transfer_not_pending'));
            }

            $debitTransaction = Transaction::withoutGlobalScopes()
                ->where('group_id', $groupId)
                ->where('type', TransactionType::Debit)
                ->firstOrFail();

            $senderWallet = Wallet::lockForUpdate()->findOrFail($debitTransaction->wallet_id);
            $amount = abs((float) $debitTransaction->amount);

            $senderWallet->decrement('locked_balance', $amount);

            return [
                'group_id' => $groupId,
                'status' => TransactionStatus::Cancelled->value,
            ];
        });

        $debitTx = Transaction::where('group_id', $groupId)
            ->where('type', TransactionType::Debit)
            ->with('wallet')
            ->first();

        $this->auditService->log(
            AuditCategory::Transaction,
            AuditSeverity::Normal,
            'transfer.cancelled',
            __('messages.audit.transfer_cancelled'),
            ['metadata' => [
                'group_id' => $groupId,
                'amount' => $debitTx ? abs((float) $debitTx->amount) : null,
                'currency' => $debitTx?->currency,
                'wallet_name' => $debitTx?->wallet?->name,
            ]],
        );

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
            ->selectRaw('SUM(ABS(amount)) as total')
            ->value('total');

        if (((float) $todayTotal + $amount) > (float) $setting->daily_transaction_limit) {
            throw ValidationException::withMessages([
                'amount' => [__('messages.daily_limit_exceeded')],
            ]);
        }
    }
}
