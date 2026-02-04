<?php

use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

test('authenticated users can list their wallets', function () {
    $user = User::factory()->create();
    Wallet::factory()->count(3)->create(['user_id' => $user->id]);
    
    Sanctum::actingAs($user);
    
    $response = $this->getJson('/api/v0/wallets');
    
    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('admins can create wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Sanctum::actingAs($admin);
    
    $response = $this->postJson('/api/v0/wallets', [
        'name' => 'New Wallet',
        'currency' => WalletCurrency::USD->value,
    ]);
    
    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'New Wallet')
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.balance', 0)
        ->assertJsonPath('data.status', 'active');
        
    $this->assertDatabaseHas('wallets', ['name' => 'New Wallet', 'user_id' => $admin->id]);
});

test('members cannot create wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    Sanctum::actingAs($member);
    
    $response = $this->postJson('/api/v0/wallets', [
        'name' => 'Illegal Wallet',
        'currency' => WalletCurrency::USD->value,
    ]);
    
    $response->assertStatus(403);
});

test('admins can toggle freeze status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $wallet = Wallet::factory()->create(['status' => WalletStatus::Active]);
    Sanctum::actingAs($admin);
    
    $response = $this->patchJson("/api/v0/wallets/{$wallet->id}/toggle-freeze");
    
    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'frozen');
        
    expect($wallet->fresh()->status)->toBe(WalletStatus::Frozen);
});

test('members cannot toggle freeze status', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $wallet = Wallet::factory()->create(['status' => WalletStatus::Active]);
    Sanctum::actingAs($member);
    
    $response = $this->patchJson("/api/v0/wallets/{$wallet->id}/toggle-freeze");
    
    $response->assertStatus(403);
});

test('admins can delete empty wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $wallet = Wallet::factory()->create(['balance' => 0]);
    Sanctum::actingAs($admin);
    
    $response = $this->deleteJson("/api/v0/wallets/{$wallet->id}");
    
    $response->assertStatus(204);
    $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
});

test('admins cannot delete wallets with balance', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $wallet = Wallet::factory()->create(['balance' => 100]);
    Sanctum::actingAs($admin);
    
    $response = $this->deleteJson("/api/v0/wallets/{$wallet->id}");
    
    $response->assertStatus(403);
});

test('members cannot delete wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $wallet = Wallet::factory()->create(['balance' => 0]);
    Sanctum::actingAs($member);
    
    $response = $this->deleteJson("/api/v0/wallets/{$wallet->id}");
    
    $response->assertStatus(403);
});
