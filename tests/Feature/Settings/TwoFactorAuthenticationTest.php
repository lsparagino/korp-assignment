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

test('qr code returns 400 when two factor is not enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-qr-code');

    $response->assertStatus(400);
});

test('recovery codes return 400 when two factor is not enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-recovery-codes');

    $response->assertStatus(400);
});

test('recovery codes can be regenerated', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-authentication');

    $originalCodes = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-recovery-codes')
        ->json();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/user/two-factor-recovery-codes');

    $response->assertOk();

    $newCodes = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/user/two-factor-recovery-codes')
        ->json();

    expect($newCodes)->not->toEqual($originalCodes);
});

test('guests cannot access two factor endpoints', function () {
    $this->postJson('/api/v0/user/two-factor-authentication')->assertUnauthorized();
    $this->getJson('/api/v0/user/two-factor-qr-code')->assertUnauthorized();
    $this->getJson('/api/v0/user/two-factor-recovery-codes')->assertUnauthorized();
    $this->deleteJson('/api/v0/user/two-factor-authentication')->assertUnauthorized();
});
