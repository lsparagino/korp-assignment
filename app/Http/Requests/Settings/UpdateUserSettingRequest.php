<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_money_received' => ['sometimes', 'boolean'],
            'notify_money_sent' => ['sometimes', 'boolean'],
            'notify_transaction_approved' => ['sometimes', 'boolean'],
            'notify_transaction_rejected' => ['sometimes', 'boolean'],
            'notify_approval_needed' => ['sometimes', 'boolean'],
            'date_format' => ['sometimes', 'string', 'max:10'],
            'number_format' => ['sometimes', 'string', 'max:10'],
            'daily_transaction_limit' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        ];
    }
}
