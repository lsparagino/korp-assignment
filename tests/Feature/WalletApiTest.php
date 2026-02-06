<?php

use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Company;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('authenticated users can list their wallets', function () {
    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    Wallet::factory()->count(3)->create([
        'user_id' => $user->id,
        'company_id' => $this->company->id,
    ]);
    
    $response = $this->actingAs($user, 'sanctum')->getJson("/api/v0/wallets?company_id={$this->company->id}");
    
    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('admins can create wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    
    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v0/wallets', [
        'name' => 'New Wallet',
        'currency' => WalletCurrency::USD->value,
        'company_id' => $this->company->id,
    ]);
    
    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Wallet')
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.balance', '0.00');
        
    $this->assertDatabaseHas('wallets', ['name' => 'New Wallet', 'user_id' => $admin->id]);
});

test('members cannot create wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);
    
    $response = $this->actingAs($member, 'sanctum')->postJson('/api/v0/wallets', [
        'name' => 'Illegal Wallet',
        'currency' => WalletCurrency::USD->value,
        'company_id' => $this->company->id,
    ]);
    
    $response->assertStatus(403);
});

test('admins can toggle freeze status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'status' => WalletStatus::Active,
        'company_id' => $this->company->id,
    ]);
    
    $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v0/wallets/{$wallet->id}/toggle-freeze?company_id={$this->company->id}");
    
    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'frozen');
        
    expect($wallet->fresh()->status)->toBe(WalletStatus::Frozen);
});

test('admins can delete empty wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'company_id' => $this->company->id,
    ]);
    
    $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v0/wallets/{$wallet->id}?company_id={$this->company->id}");
    
    $response->assertStatus(204);
    $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
});

test('wallets list is paginated', function () {
    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    Wallet::factory()->count(15)->create([
        'user_id' => $user->id,
        'company_id' => $this->company->id,
    ]);
    
    $response = $this->actingAs($user, 'sanctum')->getJson("/api/v0/wallets?per_page=10&company_id={$this->company->id}");
    
    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 15);
});
