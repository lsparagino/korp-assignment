<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

class TransactionBuilder
{
    private ?string $reference;

    private ?string $notes;

    public function __construct(
        private string $groupId,
        private string $currency,
        private TransactionStatus $status,
        private User $initiator,
        array $data
    ) {
        $this->reference = $data['reference'] ?? null;
        $this->notes = $data['notes'] ?? null;
    }

    public function debit(Wallet $senderWallet, ?Wallet $receiverWallet, float $amount, bool $isExternal, array $data): void
    {
        $this->createRow(
            walletId: $senderWallet->id,
            counterpartWalletId: $receiverWallet?->id,
            type: TransactionType::Debit,
            amount: -$amount,
            isExternal: $isExternal,
            externalAddress: $isExternal ? ($data['external_address'] ?? null) : null,
            externalName: $isExternal ? ($data['external_name'] ?? null) : null,
        );
    }

    public function credit(Wallet $receiverWallet, Wallet $senderWallet, float $amount): void
    {
        $this->createRow(
            walletId: $receiverWallet->id,
            counterpartWalletId: $senderWallet->id,
            type: TransactionType::Credit,
            amount: $amount,
            isExternal: false,
        );
    }

    private function createRow(
        int $walletId,
        ?int $counterpartWalletId,
        TransactionType $type,
        float $amount,
        bool $isExternal,
        ?string $externalAddress = null,
        ?string $externalName = null,
    ): void {
        $isAutoApproved = $this->status === TransactionStatus::Completed;

        Transaction::create([
            'group_id' => $this->groupId,
            'wallet_id' => $walletId,
            'counterpart_wallet_id' => $counterpartWalletId,
            'type' => $type,
            'amount' => $amount,
            'external' => $isExternal,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'status' => $this->status,
            'currency' => $this->currency,
            'exchange_rate' => 1.0,
            'initiator_user_id' => $this->initiator->id,
            'reviewer_user_id' => $isAutoApproved ? $this->initiator->id : null,
            'external_address' => $externalAddress,
            'external_name' => $externalName,
        ]);
    }
}
