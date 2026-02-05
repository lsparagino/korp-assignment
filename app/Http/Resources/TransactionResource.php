<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return [
            'id' => $this->id,
            'from_wallet' => new WalletResource($this->whenLoaded('fromWallet')),
            'to_wallet' => new WalletResource($this->whenLoaded('toWallet')),
            'from_wallet_id' => $this->from_wallet_id,
            'to_wallet_id' => $this->to_wallet_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => ($this->fromWallet ?? $this->toWallet)->currency->value,
            'external' => $this->external,
            'reference' => $this->reference,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
