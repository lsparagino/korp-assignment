<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitiateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'receiver_wallet_id' => ['nullable', 'integer', 'exists:wallets,id', 'required_if:external,false'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'external' => ['required', 'boolean'],
            'external_address' => ['nullable', 'required_if:external,true', 'string', 'max:255'],
            'external_name' => ['nullable', 'required_if:external,true', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
