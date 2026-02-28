<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionPendingApproval extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function via(object $notifiable): array
    {
        $setting = $notifiable->setting;

        if ($setting && ! $setting->notify_approval_needed) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notification_transaction_pending_approval'))
            ->markdown('emails.transaction-pending-approval', [
                'transaction' => $this->transaction,
                'initiatorName' => $this->transaction->initiator?->name ?? __('messages.unknown'),
                'walletName' => $this->transaction->wallet?->name ?? __('messages.unknown'),
                'amount' => number_format(abs((float) $this->transaction->amount), 2),
                'currency' => $this->transaction->currency,
                'reference' => $this->transaction->reference,
                'notes' => $this->transaction->notes,
            ]);
    }
}
