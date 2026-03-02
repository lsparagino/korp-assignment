<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

if (! defined('LOGIN_ENDPOINT')) {
    define('LOGIN_ENDPOINT', '/api/v0/login');
}
if (! defined('USER_ENDPOINT')) {
    define('USER_ENDPOINT', '/api/v0/user');
}

test('users can authenticate', function () {
    $user = User::factory()->create();

    $response = $this->postJson(LOGIN_ENDPOINT, [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'token_type', 'user']);
});

test('users with two factor enabled receive challenged response', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->postJson(LOGIN_ENDPOINT, [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['two_factor' => true]);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->postJson(LOGIN_ENDPOINT, [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v0/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Logged out']);
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(hash('xxh128', 'login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->postJson(LOGIN_ENDPOINT, [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson(USER_ENDPOINT);

    $response->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

test('guests cannot access user profile', function () {
    $response = $this->getJson(USER_ENDPOINT);

    $response->assertUnauthorized();
});

test('password confirmation fails with wrong password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson(USER_ENDPOINT.'/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});
