<?php

use App\Models\User;

const TEST_USER_NAME = 'Test User';
const TEST_EMAIL = 'test@example.com';

test('profile email change stores pending email', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson(route('settings.profile.update'), [
            'name' => TEST_USER_NAME,
            'email' => TEST_EMAIL,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated. A verification link has been sent to your new email address.');

    $user->refresh();

    expect($user->name)->toBe(TEST_USER_NAME);
    expect($user->email)->not->toBe(TEST_EMAIL);
    expect($user->pending_email)->toBe(TEST_EMAIL);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson(route('settings.profile.update'), [
            'name' => TEST_USER_NAME,
            'email' => $user->email,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson(route('settings.profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Account deleted successfully');

    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson(route('settings.profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');

    expect($user->fresh())->not->toBeNull();
});
