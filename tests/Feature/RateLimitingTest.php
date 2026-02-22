<?php

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

test('api returns 429 when rate limit is exceeded', function () {
    RateLimiter::for('api', fn () => Limit::perMinute(5));

    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user, 'sanctum')->getJson('/api/v0/user');
    }

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v0/user');

    $response->assertStatus(429);
});
