<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
use App\Http\Resources\CompanySettingResource;
use App\Http\Resources\UserSettingResource;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;

class SettingService
{
    public function __construct(private AuditService $auditService) {}

    public function getUserSettings(User $user): UserSettingResource
    {
        $setting = $user->setting ?? $user->setting()->firstOrCreate(['user_id' => $user->id]);

        return new UserSettingResource($setting);
    }

    public function updateUserSettings(User $user, array $data): UserSettingResource
    {
        $existingSetting = $user->setting;

        $setting = $user->setting()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );

        if (array_key_exists('security_threshold', $data)) {
            $this->auditService->log(
                AuditCategory::Settings,
                AuditSeverity::High,
                'settings.security_threshold_changed',
                __('messages.audit.security_threshold_changed'),
                ['company_id' => 0, 'metadata' => [
                    'changes' => [
                        'security_threshold' => [
                            'from' => $existingSetting?->security_threshold,
                            'to' => $data['security_threshold'],
                        ],
                    ],
                ]],
            );
        }

        if (array_key_exists('daily_transaction_limit', $data)) {
            $this->auditService->log(
                AuditCategory::Settings,
                AuditSeverity::High,
                'settings.daily_limit_changed',
                __('messages.audit.daily_limit_changed'),
                ['company_id' => 0, 'metadata' => [
                    'changes' => [
                        'daily_transaction_limit' => [
                            'from' => $existingSetting?->daily_transaction_limit,
                            'to' => $data['daily_transaction_limit'],
                        ],
                    ],
                ]],
            );
        }

        return new UserSettingResource($setting->fresh());
    }

    public function getCompanyThresholds(Company $company): array
    {
        return CompanySettingResource::collection($company->settings)->resolve();
    }

    public function upsertCompanyThreshold(Company $company, array $data): CompanySettingResource
    {
        $existing = $company->settings()
            ->where('currency', strtoupper($data['currency']))
            ->first();

        $setting = $company->settings()->updateOrCreate(
            [
                'company_id' => $company->id,
                'currency' => strtoupper($data['currency']),
            ],
            ['approval_threshold' => $data['approval_threshold']],
        );

        $this->auditService->log(
            AuditCategory::Settings,
            AuditSeverity::High,
            'settings.threshold_changed',
            __('messages.audit.threshold_changed'),
            ['metadata' => [
                'currency' => strtoupper($data['currency']),
                'changes' => [
                    'approval_threshold' => [
                        'from' => $existing?->approval_threshold,
                        'to' => $data['approval_threshold'],
                    ],
                ],
            ]],
        );

        return new CompanySettingResource($setting->fresh());
    }

    public function deleteCompanyThreshold(CompanySetting $setting): void
    {
        $thresholdData = [
            'currency' => $setting->currency,
            'approval_threshold' => $setting->approval_threshold,
        ];

        $setting->delete();

        $this->auditService->log(
            AuditCategory::Settings,
            AuditSeverity::High,
            'settings.threshold_deleted',
            __('messages.audit.threshold_deleted'),
            ['metadata' => $thresholdData],
        );
    }
}
