<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'counterpart_wallet' => new WalletResource($this->whenLoaded('counterpartWallet')),
            'wallet_id' => $this->wallet_id,
            'counterpart_wallet_id' => $this->counterpart_wallet_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'status' => $this->status,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'external' => $this->external,
            'external_address' => $this->external_address,
            'external_name' => $this->external_name,
            'reference' => $this->reference,
            'initiator_user_id' => $this->initiator_user_id,
            'reviewer_user_id' => $this->reviewer_user_id,
            'reject_reason' => $this->reject_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
