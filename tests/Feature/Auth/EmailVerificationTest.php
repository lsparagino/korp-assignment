<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user, 'sanctum')->getJson($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertOk(); 
    $response->assertJson(['message' => 'Email verified successfully']);
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user, 'sanctum')->getJson($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user visiting verification link receives success', function () {
    $user = User::factory()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user, 'sanctum')->getJson($verificationUrl);
    
    $response->assertOk();
    $response->assertJson(['message' => 'Email already verified']);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});