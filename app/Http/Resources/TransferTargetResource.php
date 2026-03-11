<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwn = (bool) ($this->resource->is_own ?? false);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency->value,
            'status' => $this->status->value,
            'is_own' => $isOwn,
            'balance' => $this->when($isOwn, fn () => number_format($this->balance, 2, '.', '')),
            'available_balance' => $this->when($isOwn, fn () => number_format($this->available_balance, 2, '.', '')),
        ];
    }
}
