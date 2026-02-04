<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Gate;

test('admins can create wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    
    expect(Gate::forUser($admin)->allows('create', Wallet::class))->toBeTrue();
});

test('members cannot create wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    
    expect(Gate::forUser($member)->allows('create', Wallet::class))->toBeFalse();
});

test('admins can update wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $wallet = Wallet::factory()->create();
    
    expect(Gate::forUser($admin)->allows('update', $wallet))->toBeTrue();
});

test('members cannot update wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $wallet = Wallet::factory()->create();
    
    expect(Gate::forUser($member)->allows('update', $wallet))->toBeFalse();
});

test('both can view wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create(['role' => UserRole::Member]);
    $wallet = Wallet::factory()->create();
    
    expect(Gate::forUser($admin)->allows('viewAny', Wallet::class))->toBeTrue();
    expect(Gate::forUser($member)->allows('viewAny', Wallet::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('view', $wallet))->toBeTrue();
    expect(Gate::forUser($member)->allows('view', $wallet))->toBeTrue();
});
