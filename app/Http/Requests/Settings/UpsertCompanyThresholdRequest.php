<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpsertCompanyThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3'],
            'approval_threshold' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];
    }
}
