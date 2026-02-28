<?php

namespace App\Policies;

use App\Models\CompanySetting;
use App\Models\User;

class CompanySettingPolicy
{
    public function delete(User $user, CompanySetting $companySetting): bool
    {
        return $user->companies()->where('companies.id', $companySetting->company_id)->exists();
    }
}
