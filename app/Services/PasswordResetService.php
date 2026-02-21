<?php

namespace App\Services;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class PasswordResetService
{
    public function sendPasswordResetLink(array $credentials): string
    {
        return Password::broker(config('fortify.passwords'))->sendResetLink($credentials);
    }

    public function resetPassword(array $data, ResetsUserPasswords $resets): string
    {
        return Password::broker(config('fortify.passwords'))->reset(
            $data,
            function ($user, $password) use ($resets, $data) {
                $resets->reset($user, [
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                ]);
            }
        );
    }
}
