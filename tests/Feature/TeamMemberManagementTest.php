<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Mail;

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

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v0/team-members', [
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
        ->getJson("/api/v0/team-members?company_id={$this->company->id}");

    $response->assertStatus(200)
        ->assertJsonPath('company', config('app.name'));
});

test('admins can update team members', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $wallet = Wallet::factory()->create([
        'user_id' => $this->admin->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v0/team-members/{$member->id}", [
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
        ->deleteJson("/api/v0/team-members/{$member->id}?company_id={$this->company->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

test('admins cannot delete other admins', function () {
    $otherAdmin = User::factory()->create(['role' => UserRole::Admin]);
    $otherAdmin->companies()->attach($this->company);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v0/team-members/{$otherAdmin->id}?company_id={$this->company->id}");

    $response->assertStatus(403);
});

test('members cannot invite team members', function () {
    Mail::fake();

    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')->postJson('/api/v0/team-members', [
        'name' => 'Unauthorized Member',
        'email' => 'unauthorized@example.com',
        'wallets' => [],
        'company_id' => $this->company->id,
    ]);

    $response->assertStatus(403);

    Mail::assertNotSent(\App\Mail\TeamMemberInvitation::class);
});
