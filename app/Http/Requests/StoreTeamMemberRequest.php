<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $companyId = $this->company_id;

        return $this->user()->isAdmin()
            && $companyId
            && $this->user()->companies()->where('companies.id', $companyId)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'wallets' => ['required', 'array', 'min:1'],
            'wallets.*' => ['exists:wallets,id'],
        ];
    }
}
