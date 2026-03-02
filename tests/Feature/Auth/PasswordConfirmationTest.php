<?php

use App\Models\User;

const CONFIRM_PASSWORD_ENDPOINT = '/api/v0/user/confirm-password';

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson(CONFIRM_PASSWORD_ENDPOINT, [
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Password confirmed']);
});

test('password confirmation requires authentication', function () {
    $response = $this->postJson(CONFIRM_PASSWORD_ENDPOINT, [
        'password' => 'password',
    ]);

    $response->assertUnauthorized();
});
