<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->name,
            'wallet_access' => $this->role === UserRole::Admin
                ? __('messages.full_access')
                : ($this->assigned_wallets_count.' '.__('messages.wallets')),
            'is_pending' => $this->is_pending,
            'is_current' => $this->id === auth()->id(),
            'assigned_wallets' => $this->whenLoaded('assignedWallets', fn () => $this->assignedWallets->pluck('id')),
        ];
    }
}
