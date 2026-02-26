<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ReviewTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject'],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'max:1000'],
        ];
    }
}
