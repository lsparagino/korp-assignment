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
                ['metadata' => ['new_value' => $data['security_threshold']]],
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
            ['metadata' => $data],
        );

        return new CompanySettingResource($setting->fresh());
    }

    public function deleteCompanyThreshold(CompanySetting $setting): void
    {
        $thresholdData = ['currency' => $setting->currency, 'amount' => $setting->value];

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
