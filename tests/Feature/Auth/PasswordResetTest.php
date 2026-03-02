<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

const FORGOT_PASSWORD_ENDPOINT = '/api/v0/forgot-password';
const RESET_PASSWORD_ENDPOINT = '/api/v0/reset-password';

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson(FORGOT_PASSWORD_ENDPOINT, ['email' => $user->email]);

    $response->assertOk();
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson(FORGOT_PASSWORD_ENDPOINT, ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);

    $notification = Notification::sent($user, ResetPassword::class)->first();
    $token = $notification->token;

    $response = $this->postJson(RESET_PASSWORD_ENDPOINT, [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Your password has been reset.']);
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->postJson(RESET_PASSWORD_ENDPOINT, [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertUnprocessable();
});
