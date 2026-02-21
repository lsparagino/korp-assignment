<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('invitation details can be viewed with valid token', function () {
    $user = User::factory()->create([
        'role' => UserRole::Member,
        'invitation_token' => 'valid-token-123',
    ]);
    $user->companies()->attach($this->company);

    $response = $this->getJson('/api/v0/invitation/valid-token-123');

    $response->assertOk()
        ->assertJsonStructure(['email', 'name'])
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('name', $user->name);
});

test('invitation details return 404 for invalid token', function () {
    $response = $this->getJson('/api/v0/invitation/invalid-token');

    $response->assertNotFound();
});

test('invitation can be accepted with valid token', function () {
    $token = Str::random(64);

    $user = User::factory()->create([
        'role' => UserRole::Member,
        'invitation_token' => $token,
        'email_verified_at' => null,
    ]);
    $user->companies()->attach($this->company);

    $response = $this->postJson("/api/v0/accept-invitation/{$token}", [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'access_token', 'token_type', 'user']);

    $user = $user->fresh();
    expect($user->invitation_token)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

test('invitation cannot be accepted with invalid token', function () {
    $response = $this->postJson('/api/v0/accept-invitation/invalid-token', [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertNotFound();
});

test('invitation acceptance requires password confirmation', function () {
    $token = Str::random(64);

    User::factory()->create([
        'role' => UserRole::Member,
        'invitation_token' => $token,
    ]);

    $response = $this->postJson("/api/v0/accept-invitation/{$token}", [
        'password' => 'new-password-123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});
