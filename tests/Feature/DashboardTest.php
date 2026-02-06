<?php

use App\Models\User;
use App\Models\Company;

test('guests are denied access to dashboard', function () {
    $response = $this->getJson('/api/v0/dashboard');
    $response->assertUnauthorized();
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);
    
    $this->actingAs($user, 'sanctum');

    $response = $this->getJson("/api/v0/dashboard?company_id={$company->id}");
    $response->assertOk()
        ->assertJsonStructure([
            'balances',
            'top_wallets',
            'others',
            'transactions',
            'wallets',
        ]);
});