<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionRejected extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function via(object $notifiable): array
    {
        $setting = $notifiable->setting;

        if ($setting && ! $setting->notify_transaction_rejected) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Transaction Rejected'))
            ->markdown('emails.transaction-rejected', [
                'transaction' => $this->transaction,
                'reviewerName' => $this->transaction->reviewer?->name ?? __('Unknown'),
                'walletName' => $this->transaction->wallet?->name ?? __('Unknown'),
                'amount' => number_format(abs((float) $this->transaction->amount), 2),
                'currency' => $this->transaction->currency,
                'reference' => $this->transaction->reference,
                'rejectReason' => $this->transaction->reject_reason,
            ]);
    }
}
