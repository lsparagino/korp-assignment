<?php

use App\Mail\TeamMemberInvitation;
use App\Models\User;

test('invitation mailable has correct subject', function () {
    $user = User::factory()->create();
    $mailable = new TeamMemberInvitation($user);

    $mailable->assertHasSubject('Team Member Invitation');
});

test('invitation mailable uses correct markdown view', function () {
    $user = User::factory()->create();
    $mailable = new TeamMemberInvitation($user);

    $mailable->assertSeeInOrderInHtml([$user->name]);
});

test('invitation mailable has no attachments', function () {
    $user = User::factory()->create();
    $mailable = new TeamMemberInvitation($user);

    expect($mailable->attachments())->toBeEmpty();
});
