<?php

namespace App\Http\Requests\Api;

use App\Enums\WalletStatus;
use App\Http\Requests\Concerns\VerifiesIdentity;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class InitiateTransferRequest extends FormRequest
{
    use VerifiesIdentity;

    public function authorize(): bool
    {
        $senderWallet = Wallet::find($this->input('sender_wallet_id'));

        if (! $senderWallet || $this->user()->cannot('transfer', $senderWallet)) {
            return false;
        }

        if (! $this->boolean('external') && $this->input('receiver_wallet_id')) {
            $receiverWallet = Wallet::find($this->input('receiver_wallet_id'));

            if (! $receiverWallet
                || $receiverWallet->status === WalletStatus::Frozen
                || $receiverWallet->company_id !== $senderWallet->company_id) {
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
            'password' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        $setting = $this->user()->setting;

        if (! $setting || $setting->security_threshold === null) {
            return;
        }

        if ((float) $this->input('amount') > (float) $setting->security_threshold) {
            if (! $this->filled('password') && ! $this->filled('code')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'identity' => ['Identity verification is required for this transfer amount.'],
                ]);
            }

            $this->verifyIdentity();
        }
    }
}
