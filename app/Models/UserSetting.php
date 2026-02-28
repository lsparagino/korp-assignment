<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'notify_money_received',
        'notify_money_sent',
        'notify_transaction_approved',
        'notify_transaction_rejected',
        'notify_approval_needed',
        'date_format',
        'number_format',
        'daily_transaction_limit',
        'security_threshold',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'notify_money_received' => 'boolean',
            'notify_money_sent' => 'boolean',
            'notify_transaction_approved' => 'boolean',
            'notify_transaction_rejected' => 'boolean',
            'notify_approval_needed' => 'boolean',
            'daily_transaction_limit' => 'decimal:2',
            'security_threshold' => 'decimal:2',
        ];
    }
}
