<?php

use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;

test('requests without company_id return empty data for wallets', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);
    Wallet::factory()->count(3)->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v0/wallets');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('requests without company_id return empty data for team members', function () {
    $user = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v0/team-members');

    $response->assertOk()
        ->assertJsonPath('data', []);
});

test('requests with unauthorized company_id are forbidden', function () {
    $user = User::factory()->create();
    $ownCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user->companies()->attach($ownCompany);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v0/wallets?company_id={$otherCompany->id}");

    $response->assertForbidden();
});

test('requests with unauthorized company_id via header are forbidden', function () {
    $user = User::factory()->create();
    $ownCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user->companies()->attach($ownCompany);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/wallets', ['X-Company-Id' => $otherCompany->id]);

    $response->assertForbidden();
});

test('middleware merges company_id from header into request', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);
    Wallet::factory()->count(2)->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v0/wallets', ['X-Company-Id' => $company->id]);

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});
