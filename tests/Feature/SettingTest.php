<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->member = User::factory()->create(['role' => UserRole::Member]);
    $this->admin->companies()->attach($this->company);
    $this->member->companies()->attach($this->company);
});

describe('User Preferences', function () {
    it('returns default preferences for authenticated user', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v0/settings/preferences');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'notify_money_received',
                    'notify_money_sent',
                    'notify_transaction_approved',
                    'notify_transaction_rejected',
                    'notify_approval_needed',
                    'date_format',
                    'number_format',
                    'daily_transaction_limit',
                ],
            ]);
    });

    it('updates user preferences', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v0/settings/preferences', [
                'notify_money_received' => false,
                'date_format' => 'de-DE',
                'daily_transaction_limit' => 5000,
            ]);

        $response->assertOk();
        expect($response->json('data.notify_money_received'))->toBeFalse();
        expect($response->json('data.date_format'))->toBe('de-DE');
        expect($response->json('data.daily_transaction_limit'))->toBe('5000.00');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v0/settings/preferences')
            ->assertUnauthorized();
    });
});

describe('Company Thresholds', function () {
    it('lists thresholds for admin', function () {
        CompanySetting::create([
            'company_id' => $this->company->id,
            'currency' => 'EUR',
            'approval_threshold' => 1000,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v0/settings/thresholds?company_id='.$this->company->id);

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.currency'))->toBe('EUR');
    });

    it('creates a threshold for admin', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v0/settings/thresholds?company_id='.$this->company->id, [
                'currency' => 'USD',
                'approval_threshold' => 2500,
            ]);

        $response->assertOk();
        expect($response->json('data.currency'))->toBe('USD');
        expect($response->json('data.approval_threshold'))->toBe('2500.00');
    });

    it('upserts threshold for same currency', function () {
        CompanySetting::create([
            'company_id' => $this->company->id,
            'currency' => 'EUR',
            'approval_threshold' => 1000,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v0/settings/thresholds?company_id='.$this->company->id, [
                'currency' => 'EUR',
                'approval_threshold' => 5000,
            ]);

        $response->assertOk();
        expect($response->json('data.approval_threshold'))->toBe('5000.00');
        expect(CompanySetting::where('company_id', $this->company->id)->where('currency', 'EUR')->count())->toBe(1);
    });

    it('deletes a threshold for admin', function () {
        $setting = CompanySetting::create([
            'company_id' => $this->company->id,
            'currency' => 'GBP',
            'approval_threshold' => 500,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v0/settings/thresholds/{$setting->id}?company_id=".$this->company->id);

        $response->assertNoContent();
        expect(CompanySetting::find($setting->id))->toBeNull();
    });

    it('forbids non-admin from upserting thresholds', function () {
        $this->actingAs($this->member, 'sanctum')
            ->putJson('/api/v0/settings/thresholds?company_id='.$this->company->id, [
                'currency' => 'EUR',
                'approval_threshold' => 1000,
            ])
            ->assertForbidden();
    });
});
