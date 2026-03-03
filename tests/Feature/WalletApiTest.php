<?php

use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;

if (! defined('WALLETS_ENDPOINT')) {
    define('WALLETS_ENDPOINT', '/api/v0/wallets');
}
if (! defined('NEW_WALLET_NAME')) {
    define('NEW_WALLET_NAME', 'New Wallet');
}
if (! defined('UPDATED_WALLET_NAME')) {
    define('UPDATED_WALLET_NAME', 'Updated Wallet Name');
}

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

    $response = $this->actingAs($user, 'sanctum')->getJson(WALLETS_ENDPOINT."?company_id={$this->company->id}");

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

test('admins can create wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $response = $this->actingAs($admin, 'sanctum')->postJson(WALLETS_ENDPOINT, [
        'name' => NEW_WALLET_NAME,
        'currency' => WalletCurrency::USD->value,
        'company_id' => $this->company->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', NEW_WALLET_NAME)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.balance', '0.00');

    $this->assertDatabaseHas('wallets', ['name' => NEW_WALLET_NAME, 'user_id' => $admin->id]);
});

test('members cannot create wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $response = $this->actingAs($member, 'sanctum')->postJson(WALLETS_ENDPOINT, [
        'name' => 'Illegal Wallet',
        'currency' => WalletCurrency::USD->value,
        'company_id' => $this->company->id,
    ]);

    $response->assertForbidden();
});

test('admins can toggle freeze status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'status' => WalletStatus::Active,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->patchJson(WALLETS_ENDPOINT."/{$wallet->id}/toggle-freeze?company_id={$this->company->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'frozen');

    expect($wallet->fresh()->status)->toBe(WalletStatus::Frozen);
});

test('admins can delete empty wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->deleteJson(WALLETS_ENDPOINT."/{$wallet->id}?company_id={$this->company->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
});

test('wallets list is paginated', function () {
    $user = User::factory()->create();
    $user->companies()->attach($this->company);
    Wallet::factory()->count(15)->create([
        'user_id' => $user->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson(WALLETS_ENDPOINT."?per_page=10&company_id={$this->company->id}");

    $response->assertSuccessful()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 15);
});

test('admins can view a single wallet', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'user_id' => $admin->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson(WALLETS_ENDPOINT."/{$wallet->id}?company_id={$this->company->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $wallet->id)
        ->assertJsonPath('data.name', $wallet->name);
});

test('members can only view wallets assigned to them', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $wallet = Wallet::factory()->create([
        'user_id' => $admin->id,
        'company_id' => $this->company->id,
    ]);

    // Member cannot view unassigned wallet
    $response = $this->actingAs($member, 'sanctum')
        ->getJson(WALLETS_ENDPOINT."/{$wallet->id}?company_id={$this->company->id}");

    $response->assertForbidden();

    // After assignment, member can view
    $wallet->members()->attach($member);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(WALLETS_ENDPOINT."/{$wallet->id}?company_id={$this->company->id}");

    $response->assertOk();
});

test('admins can update a wallet', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);
    $wallet = Wallet::factory()->create([
        'user_id' => $admin->id,
        'company_id' => $this->company->id,
        'currency' => WalletCurrency::USD,
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->putJson(WALLETS_ENDPOINT."/{$wallet->id}?company_id={$this->company->id}", [
            'name' => UPDATED_WALLET_NAME,
            'currency' => WalletCurrency::USD->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.name', UPDATED_WALLET_NAME);

    expect($wallet->fresh()->name)->toBe(UPDATED_WALLET_NAME);
});

test('guests are denied access to wallets', function () {
    $response = $this->getJson(WALLETS_ENDPOINT);

    $response->assertUnauthorized();
});
