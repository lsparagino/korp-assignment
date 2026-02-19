<?php

use App\Models\User;

test('api returns 429 when rate limit is exceeded', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($user, 'sanctum')->getJson('/api/v0/user');
    }

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v0/user');

    $response->assertStatus(429);
});
