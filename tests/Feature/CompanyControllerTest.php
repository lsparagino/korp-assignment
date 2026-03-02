<?php

use App\Models\Company;
use App\Models\User;

const COMPANIES_ENDPOINT = '/api/v0/companies';

test('guests are denied access to companies', function () {
    $response = $this->getJson(COMPANIES_ENDPOINT);

    $response->assertUnauthorized();
});

test('authenticated users can list their companies', function () {
    $user = User::factory()->create();
    $companies = Company::factory()->count(3)->create();
    $user->companies()->attach($companies);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(COMPANIES_ENDPOINT);

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('users only see companies they belong to', function () {
    $user = User::factory()->create();
    $ownCompany = Company::factory()->create();
    Company::factory()->create(); // other company

    $user->companies()->attach($ownCompany);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(COMPANIES_ENDPOINT);

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('currencies endpoint returns all wallet currencies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v0/currencies?company_id={$company->id}");

    $response->assertOk()
        ->assertJsonCount(3); // USD, EUR, GBP

    $currencies = $response->json();
    expect($currencies)->toContain('USD')
        ->toContain('EUR')
        ->toContain('GBP');
});
