<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('two factor authentication can be enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-authentication')
        ->assertStatus(200);

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor qr code can be retrieved', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-authentication');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-qr-code');

    $response->assertStatus(200)
        ->assertJsonStructure(['svg']);
});

test('two factor recovery codes can be retrieved', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-authentication');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-recovery-codes');

    $response->assertStatus(200);
    expect($response->json())->toBeArray()->not->toBeEmpty();
});

test('two factor authentication can be disabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-authentication');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v0/user/two-factor-authentication')
        ->assertStatus(200);

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
