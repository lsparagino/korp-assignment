<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressBookEntryRequest extends FormRequest
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
            'address' => [
                'required',
                'string',
                'max:255',
                Rule::unique('address_book_entries')
                    ->where('user_id', $this->user()->id)
                    ->where('company_id', $this->company_id),
            ],
        ];
    }
}
