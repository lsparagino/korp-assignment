<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notify_money_received' => $this->notify_money_received,
            'notify_money_sent' => $this->notify_money_sent,
            'notify_transaction_approved' => $this->notify_transaction_approved,
            'notify_transaction_rejected' => $this->notify_transaction_rejected,
            'notify_approval_needed' => $this->notify_approval_needed,
            'date_format' => $this->date_format,
            'number_format' => $this->number_format,
            'daily_transaction_limit' => $this->daily_transaction_limit,
            'security_threshold' => $this->security_threshold,
        ];
    }
}
