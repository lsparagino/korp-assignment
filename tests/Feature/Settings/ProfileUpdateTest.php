<?php

use App\Models\User;

test('profile email change stores pending email', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->patchJson(route('settings.profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated. A verification link has been sent to your new email address.');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->not->toBe('test@example.com');
    expect($user->pending_email)->toBe('test@example.com');
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
