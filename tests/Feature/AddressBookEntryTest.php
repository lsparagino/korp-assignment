<?php

use App\Models\AddressBookEntry;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
});

test('authenticated users can list their address book entries', function () {
    AddressBookEntry::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    // Another user's entries should not appear
    $otherUser = User::factory()->create();
    $otherUser->companies()->attach($this->company);
    AddressBookEntry::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v0/address-book?company_id={$this->company->id}");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('entries are ordered by name', function () {
    AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'name' => 'Zeta Corp',
    ]);
    AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'name' => 'Alpha Inc',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v0/address-book?company_id={$this->company->id}");

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toBe(['Alpha Inc', 'Zeta Corp']);
});

test('users can create an address book entry', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v0/address-book?company_id={$this->company->id}", [
            'name' => 'Acme Vendor',
            'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Acme Vendor')
        ->assertJsonPath('data.address', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh');

    $this->assertDatabaseHas('address_book_entries', [
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'name' => 'Acme Vendor',
    ]);
});

test('validation rejects missing name', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v0/address-book?company_id={$this->company->id}", [
            'address' => 'some-address',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('validation rejects missing address', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v0/address-book?company_id={$this->company->id}", [
            'name' => 'Some Name',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('address');
});

test('validation rejects duplicate address for same user and company', function () {
    AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'address' => 'duplicate-addr',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v0/address-book?company_id={$this->company->id}", [
            'name' => 'Another Name',
            'address' => 'duplicate-addr',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('address');
});

test('same address is allowed for different companies', function () {
    $otherCompany = Company::factory()->create();
    $this->user->companies()->attach($otherCompany);

    AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'address' => 'shared-addr',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v0/address-book?company_id={$otherCompany->id}", [
            'name' => 'Same Addr Different Company',
            'address' => 'shared-addr',
        ]);

    $response->assertStatus(201);
});

test('users can update their own entry', function () {
    $entry = AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/v0/address-book/{$entry->id}?company_id={$this->company->id}", [
            'name' => 'Updated Name',
            'address' => 'updated-address',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.address', 'updated-address');
});

test('users cannot update another users entry', function () {
    $otherUser = User::factory()->create();
    $otherUser->companies()->attach($this->company);
    $entry = AddressBookEntry::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/v0/address-book/{$entry->id}?company_id={$this->company->id}", [
            'name' => 'Hacked',
            'address' => 'hacked-addr',
        ]);

    $response->assertForbidden();
});

test('users can delete their own entry', function () {
    $entry = AddressBookEntry::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v0/address-book/{$entry->id}?company_id={$this->company->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('address_book_entries', ['id' => $entry->id]);
});

test('users cannot delete another users entry', function () {
    $otherUser = User::factory()->create();
    $otherUser->companies()->attach($this->company);
    $entry = AddressBookEntry::factory()->create([
        'user_id' => $otherUser->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v0/address-book/{$entry->id}?company_id={$this->company->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('address_book_entries', ['id' => $entry->id]);
});

test('guests are denied access to address book', function () {
    $response = $this->getJson('/api/v0/address-book');

    $response->assertUnauthorized();
});
