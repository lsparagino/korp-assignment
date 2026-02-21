<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;

class ConfirmTwoFactorRequest extends TwoFactorAuthenticationRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
