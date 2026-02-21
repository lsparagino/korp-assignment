<?php

use App\Models\Company;
use App\Models\User;

test('guests are denied access to companies', function () {
    $response = $this->getJson('/api/v0/companies');

    $response->assertUnauthorized();
});

test('authenticated users can list their companies', function () {
    $user = User::factory()->create();
    $companies = Company::factory()->count(3)->create();
    $user->companies()->attach($companies);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/companies');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('users only see companies they belong to', function () {
    $user = User::factory()->create();
    $ownCompany = Company::factory()->create();
    Company::factory()->create(); // other company

    $user->companies()->attach($ownCompany);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/companies');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
