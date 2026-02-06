<?php

use App\Models\User;

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v0/user/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Password confirmed']);
});

test('password confirmation requires authentication', function () {
    $response = $this->postJson('/api/v0/user/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertUnauthorized();
});