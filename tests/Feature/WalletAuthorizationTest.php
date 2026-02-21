<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('admins can create wallets', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    expect(Gate::forUser($admin)->allows('create', Wallet::class))->toBeTrue();
});

test('members cannot create wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    expect(Gate::forUser($member)->allows('create', Wallet::class))->toBeFalse();
});

test('admins can update wallets in their company', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach($this->company);

    $wallet = Wallet::factory()->create(['company_id' => $this->company->id]);

    expect(Gate::forUser($admin)->allows('update', $wallet))->toBeTrue();
});

test('members cannot update wallets', function () {
    $member = User::factory()->create(['role' => UserRole::Member]);
    $member->companies()->attach($this->company);

    $wallet = Wallet::factory()->create(['company_id' => $this->company->id]);

    expect(Gate::forUser($member)->allows('update', $wallet))->toBeFalse();
});

test('admins can view any wallet in company, members only assigned', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create(['role' => UserRole::Member]);
    $otherMember = User::factory()->create(['role' => UserRole::Member]);

    $this->company->users()->attach([$admin->id, $member->id, $otherMember->id]);

    $wallet = Wallet::factory()->create([
        'user_id' => $admin->id,
        'company_id' => $this->company->id,
    ]);

    // Admin can view
    expect(Gate::forUser($admin)->allows('view', $wallet))->toBeTrue();

    // Member cannot view (not owner, not assigned)
    expect(Gate::forUser($member)->allows('view', $wallet))->toBeFalse();

    // Assign member
    $wallet->members()->attach($member);
    expect(Gate::forUser($member)->allows('view', $wallet))->toBeTrue();
});
