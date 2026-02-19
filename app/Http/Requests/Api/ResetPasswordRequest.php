<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\Fortify;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            Fortify::email() => ['required', 'email'],
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required'],
        ];
    }
}
