<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'currency' => $this->currency->value,
            'balance' => number_format($this->balance, 2, '.', ''),
            'locked_balance' => number_format((float) ($this->locked_balance ?? 0), 2, '.', ''),
            'available_balance' => number_format($this->available_balance, 2, '.', ''),
            'status' => $this->status->value,
            'can_delete' => $request->user()?->isAdmin() && ! $this->resource->hasTransactions(),
            'created_at' => $this->created_at,
        ];
    }
}
