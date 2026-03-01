<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'user_id',
        'response_code',
        'response_body',
    ];

    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('created_at', '<', now()->subDay());
    }

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
        ];
    }
}
