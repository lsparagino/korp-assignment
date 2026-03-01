<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class PasswordResetService
{
    public function __construct(private AuditService $auditService) {}

    public function sendPasswordResetLink(array $credentials): string
    {
        return Password::broker(config('fortify.passwords'))->sendResetLink($credentials);
    }

    public function resetPassword(array $data, ResetsUserPasswords $resets): string
    {
        $status = Password::broker(config('fortify.passwords'))->reset(
            $data,
            function ($user, $password) use ($resets, $data) {
                $resets->reset($user, [
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->auditService->log(
                AuditCategory::Auth,
                AuditSeverity::Medium,
                'user.password_reset',
                __('messages.audit.user_password_reset'),
            );
        }

        return $status;
    }
}
