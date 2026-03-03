<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

if (! defined('VERIFICATION_NOTIFICATION_ENDPOINT')) {
    define('VERIFICATION_NOTIFICATION_ENDPOINT', '/api/v0/email/verification-notification');
}

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(VERIFICATION_NOTIFICATION_ENDPOINT);

    $response->assertOk();
    $response->assertJson(['message' => 'Verification link sent']);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('does not send verification notification if email already verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(VERIFICATION_NOTIFICATION_ENDPOINT);

    $response->assertBadRequest();
    $response->assertJson(['message' => 'Email already verified']);

    Notification::assertNothingSent();
});
