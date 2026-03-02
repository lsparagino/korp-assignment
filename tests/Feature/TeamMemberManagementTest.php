<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Mail;

const TEAM_MEMBERS_ENDPOINT = '/api/v0/team-members';

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->admin->companies()->attach($this->company);
});

test('admins can invite team members', function () {
    Mail::fake();

    $wallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')->postJson(TEAM_MEMBERS_ENDPOINT, [
        'name' => 'New Member',
        'email' => 'member@example.com',
        'wallets' => [$wallet->id],
        'company_id' => $this->company->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Member invited successfully');

    $this->assertDatabaseHas('users', [
        'email' => 'member@example.com',
        'role' => UserRole::Member,
    ]);

    Mail::assertSent(\App\Mail\TeamMemberInvitation::class);
});

test('admins can list team members', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson(TEAM_MEMBERS_ENDPOINT."?company_id={$this->company->id}");

    $response->assertStatus(200)
        ->assertJsonPath('company', config('app.name'));
});

test('admins can view a single team member', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}?company_id={$this->company->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $member->id)
        ->assertJsonPath('name', $member->name)
        ->assertJsonPath('email', $member->email)
        ->assertJsonPath('role', 'Member');
});

test('members can view a single team member', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $otherMember = User::factory()->create(['role' => UserRole::Member]);
    $otherMember->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(TEAM_MEMBERS_ENDPOINT."/{$otherMember->id}?company_id={$this->company->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $otherMember->id);
});

test('admins can update team members', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $wallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}", [
            'name' => 'Updated Name',
            'email' => $member->email,
            'wallets' => [$wallet->id],
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Member updated successfully');

    expect($member->fresh()->name)->toBe('Updated Name');
});

test('admins can delete team members', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->deleteJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}?company_id={$this->company->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

test('admins cannot delete other admins', function () {
    $otherAdmin = User::factory()->create(['role' => UserRole::Admin]);
    $otherAdmin->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->deleteJson(TEAM_MEMBERS_ENDPOINT."/{$otherAdmin->id}?company_id={$this->company->id}");

    $response->assertStatus(403);
});

test('members cannot invite team members', function () {
    Mail::fake();

    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')->postJson(TEAM_MEMBERS_ENDPOINT, [
        'name' => 'Unauthorized Member',
        'email' => 'unauthorized@example.com',
        'wallets' => [],
        'company_id' => $this->company->id,
    ]);

    $response->assertStatus(403);

    Mail::assertNotSent(\App\Mail\TeamMemberInvitation::class);
});

test('admins can promote a member to manager', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->patchJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}/promote", [
            'role' => 'manager',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Member role updated successfully');

    expect($member->fresh()->role)->toBe(UserRole::Manager);
});

test('admins can demote a manager to member', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $manager->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->patchJson(TEAM_MEMBERS_ENDPOINT."/{$manager->id}/promote", [
            'role' => 'member',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(200);

    expect($manager->fresh()->role)->toBe(UserRole::Member);
});

test('non-admins cannot promote team members', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $otherMember = User::factory()->create(['role' => UserRole::Member]);
    $otherMember->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')
        ->patchJson(TEAM_MEMBERS_ENDPOINT."/{$otherMember->id}/promote", [
            'role' => 'manager',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(403);
});

test('managers cannot promote team members', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $manager->companies()->attach($this->company);

    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($manager, 'sanctum')
        ->patchJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}/promote", [
            'role' => 'manager',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(403);
});

test('admins cannot promote to admin role', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->patchJson(TEAM_MEMBERS_ENDPOINT."/{$member->id}/promote", [
            'role' => 'admin',
            'company_id' => $this->company->id,
        ]);

    $response->assertStatus(422);
});

test('managers cannot invite team members', function () {
    Mail::fake();

    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $manager->companies()->attach($this->company);

    $response = $this->actingAs($manager, 'sanctum')->postJson(TEAM_MEMBERS_ENDPOINT, [
        'name' => 'New Member',
        'email' => 'new@example.com',
        'wallets' => [],
        'company_id' => $this->company->id,
    ]);

    $response->assertStatus(403);

    Mail::assertNotSent(\App\Mail\TeamMemberInvitation::class);
});

test('admins can delete managers', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $manager->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->deleteJson(TEAM_MEMBERS_ENDPOINT."/{$manager->id}?company_id={$this->company->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $manager->id]);
});
