<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionCompleted extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function via(object $notifiable): array
    {
        $setting = $notifiable->setting;

        if ($setting && ! $setting->notify_money_sent) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notification_transaction_completed'))
            ->markdown('emails.transaction-completed', [
                'transaction' => $this->transaction,
                'walletName' => $this->transaction->wallet?->name ?? __('messages.unknown'),
                'amount' => number_format(abs((float) $this->transaction->amount), 2),
                'currency' => $this->transaction->currency,
                'reference' => $this->transaction->reference,
            ]);
    }
}
