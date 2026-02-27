<?php

namespace App\Services;

use App\Http\Resources\CompanySettingResource;
use App\Http\Resources\UserSettingResource;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;

class SettingService
{
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

        return new CompanySettingResource($setting->fresh());
    }

    public function deleteCompanyThreshold(CompanySetting $setting): void
    {
        $setting->delete();
    }
}
