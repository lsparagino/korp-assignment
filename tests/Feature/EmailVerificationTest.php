<?php

use App\Models\Company;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('registration sends verification email', function () {
    Notification::fake();

    $response = $this->postJson('/api/v0/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('user.email_verified_at', null);

    Notification::assertSentTo(
        User::where('email', 'test@example.com')->first(),
        VerifyEmailNotification::class
    );
});

test('unverified user can resend verification email', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/email/verification-notification');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Verification link sent');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('verified user cannot resend verification email without pending email', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/email/verification-notification');

    $response->assertStatus(400)
        ->assertJsonPath('message', 'Email already verified');
});

test('user can verify email with valid signed link', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->getJson($url);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email verified successfully');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verify fails with invalid signature', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->getJson("/api/v0/email/verify/{$user->id}/".sha1($user->email).'?expires=9999999999&signature=invalid');

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Invalid or expired verification link');
});

test('verify fails with invalid hash', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong@email.com')]
    );

    $response = $this->getJson($url);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'Invalid verification link');
});

test('profile email change stores pending email', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v0/settings/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->email)->not->toBe('new@example.com');
    expect($user->pending_email)->toBe('new@example.com');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('verifying pending email swaps it', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'pending_email' => 'new@example.com',
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('new@example.com')]
    );

    $response = $this->getJson($url);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Email address updated and verified successfully');

    $user->refresh();
    expect($user->email)->toBe('new@example.com');
    expect($user->pending_email)->toBeNull();
});

test('user can cancel pending email change', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'pending_email' => 'new@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v0/settings/pending-email');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Pending email change cancelled');

    expect($user->fresh()->pending_email)->toBeNull();
});
