<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/email/verification-notification');

    $response->assertOk();
    $response->assertJson(['message' => 'Verification link sent']);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('does not send verification notification if email already verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v0/email/verification-notification');

    $response->assertStatus(400);
    $response->assertJson(['message' => 'Email already verified']);

    Notification::assertNothingSent();
});
