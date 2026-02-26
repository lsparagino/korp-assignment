<?php

namespace App\Http\Requests\Api;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class InitiateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $senderWallet = Wallet::find($this->input('sender_wallet_id'));

        if (! $senderWallet || $this->user()->cannot('transfer', $senderWallet)) {
            return false;
        }

        if (! $this->boolean('external') && $this->input('receiver_wallet_id')) {
            $receiverWallet = Wallet::find($this->input('receiver_wallet_id'));

            if (! $receiverWallet || $receiverWallet->status->value === 'frozen') {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'sender_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'receiver_wallet_id' => ['nullable', 'integer', 'exists:wallets,id', 'required_if:external,false'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'external' => ['required', 'boolean'],
            'external_address' => ['nullable', 'required_if:external,true', 'string', 'max:255'],
            'external_name' => ['nullable', 'required_if:external,true', 'string', 'max:255'],
            'reference' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
