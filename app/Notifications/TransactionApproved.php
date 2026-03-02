<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionApproved extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function via(object $notifiable): array
    {
        $setting = $notifiable->setting;

        if ($setting && ! $setting->notify_transaction_approved) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notification_transaction_approved'))
            ->markdown('emails.transaction-approved', [
                'transaction' => $this->transaction,
                'reviewerName' => $this->transaction->reviewer?->name ?? __('messages.unknown'),
                'walletName' => $this->transaction->wallet?->name ?? __('messages.unknown'),
                'amount' => number_format(abs((float) $this->transaction->amount), 2),
                'currency' => $this->transaction->source_currency,
                'reference' => $this->transaction->reference,
            ]);
    }
}
