<?php

use App\Models\User;
use Laravel\Fortify\Features;

const TWO_FACTOR_AUTH_ENDPOINT = '/api/v0/user/two-factor/authentication';
const TWO_FACTOR_QR_ENDPOINT = '/api/v0/user/two-factor/qr-code';
const TWO_FACTOR_RECOVERY_ENDPOINT = '/api/v0/user/two-factor/recovery-codes';

beforeEach(function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }
});

test('two factor authentication can be enabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_AUTH_ENDPOINT)
        ->assertSuccessful();

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor qr code can be retrieved', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_AUTH_ENDPOINT);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_QR_ENDPOINT);

    $response->assertSuccessful()
        ->assertJsonStructure(['svg']);
});

test('two factor recovery codes can be retrieved', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_AUTH_ENDPOINT);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_RECOVERY_ENDPOINT);

    $response->assertSuccessful();
    expect($response->json())->toBeArray()->not->toBeEmpty();
});

test('two factor authentication can be disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_AUTH_ENDPOINT);

    $this->actingAs($user, 'sanctum')
        ->deleteJson(TWO_FACTOR_AUTH_ENDPOINT)
        ->assertSuccessful();

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('qr code returns 400 when two factor is not enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_QR_ENDPOINT);

    $response->assertBadRequest();
});

test('recovery codes return 400 when two factor is not enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_RECOVERY_ENDPOINT);

    $response->assertBadRequest();
});

test('recovery codes can be regenerated', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_AUTH_ENDPOINT);

    $originalCodes = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_RECOVERY_ENDPOINT)
        ->json();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(TWO_FACTOR_RECOVERY_ENDPOINT);

    $response->assertOk();

    $newCodes = $this->actingAs($user, 'sanctum')
        ->getJson(TWO_FACTOR_RECOVERY_ENDPOINT)
        ->json();

    expect($newCodes)->not->toEqual($originalCodes);
});

test('guests cannot access two factor endpoints', function () {
    $this->postJson(TWO_FACTOR_AUTH_ENDPOINT)->assertUnauthorized();
    $this->getJson(TWO_FACTOR_QR_ENDPOINT)->assertUnauthorized();
    $this->getJson(TWO_FACTOR_RECOVERY_ENDPOINT)->assertUnauthorized();
    $this->deleteJson(TWO_FACTOR_AUTH_ENDPOINT)->assertUnauthorized();
});
