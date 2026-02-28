<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

trait VerifiesIdentity
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function identityRules(): array
    {
        return [
            'password' => ['required_without:code', 'nullable', 'string'],
            'code' => ['required_without:password', 'nullable', 'string'],
        ];
    }

    protected function verifyIdentity(): void
    {
        $user = $this->user();

        if ($this->filled('code') && $user->two_factor_secret && $user->two_factor_confirmed_at) {
            if (! app(TwoFactorAuthenticationProvider::class)->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $this->input('code')
            )) {
                throw ValidationException::withMessages([
                    'code' => [__('messages.invalid_two_factor_code')],
                ]);
            }

            return;
        }

        if (! $this->filled('password') || ! Hash::check($this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function stripIdentityFields(array $validated): array
    {
        unset($validated['password'], $validated['code']);

        return $validated;
    }
}
