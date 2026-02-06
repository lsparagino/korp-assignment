<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('users with two factor enabled receive challenged response on login', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->postJson('/api/v0/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['two_factor' => true]);
    $this->assertGuest();
});

test('users can challenge with recovery code', function () {
     if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->postJson('/api/v0/two-factor-challenge', [
        'user_id' => $user->id,
        'recovery_code' => 'code1',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'user']);
});