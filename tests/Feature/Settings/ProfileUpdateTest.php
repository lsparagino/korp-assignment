<?php

use App\Models\User;

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson(route('settings.profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson(route('settings.profile.update'), [
            'name' => 'Test User',
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