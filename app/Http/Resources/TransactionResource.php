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
            'currency' => $this->wallet?->currency->value ?? ($this->whenLoaded('wallet') ? null : null),
            'external' => $this->external,
            'reference' => $this->reference,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
