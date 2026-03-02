<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

const AUDIT_LOGS_ENDPOINT = '/api/v0/audit-logs';

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('guests are denied access to audit logs', function () {
    $response = $this->getJson(AUDIT_LOGS_ENDPOINT);

    $response->assertUnauthorized();
});

test('non-admin users are forbidden from accessing audit logs', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}");

    $response->assertForbidden();
});

test('admin users can access audit logs', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['next_cursor'],
        ]);
});

test('audit logs validates per_page parameter', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}&per_page=200");

    $response->assertStatus(422)
        ->assertJsonValidationErrors('per_page');
});

test('audit logs validates category filter', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}&category=invalid");

    $response->assertStatus(422)
        ->assertJsonValidationErrors('category');
});

test('audit logs validates date range', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}&date_from=2026-01-10&date_to=2026-01-01");

    $response->assertStatus(422)
        ->assertJsonValidationErrors('date_to');
});

test('managers are forbidden from accessing audit logs', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $manager->companies()->attach($this->company);

    $response = $this->actingAs($manager, 'sanctum')
        ->getJson(AUDIT_LOGS_ENDPOINT."?company_id={$this->company->id}");

    $response->assertForbidden();
});
