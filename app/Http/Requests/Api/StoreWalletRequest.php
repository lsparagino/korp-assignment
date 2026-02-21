<?php

namespace App\Http\Requests\Api;

use App\Enums\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::enum(WalletCurrency::class)],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ];
    }
}
