<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateUserSettingRequest;
use App\Http\Requests\Settings\UpsertCompanyThresholdRequest;
use App\Http\Resources\CompanySettingResource;
use App\Http\Resources\UserSettingResource;
use App\Models\CompanySetting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService) {}

    public function showUserSettings(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->settingService->getUserSettings($request->user())
            ->response()
            ->setStatusCode(200);
    }

    public function updateUserSettings(UpdateUserSettingRequest $request): UserSettingResource
    {
        return $this->settingService->updateUserSettings($request->user(), $request->validated());
    }

    public function indexCompanyThresholds(Request $request): JsonResponse
    {
        $company = $request->user()->companies()->findOrFail($request->company_id);

        return response()->json([
            'data' => $this->settingService->getCompanyThresholds($company),
        ]);
    }

    public function upsertCompanyThreshold(UpsertCompanyThresholdRequest $request): CompanySettingResource
    {
        $company = $request->user()->companies()->findOrFail($request->company_id);

        return $this->settingService->upsertCompanyThreshold($company, $request->validated());
    }

    public function destroyCompanyThreshold(Request $request, CompanySetting $threshold): Response
    {
        $company = $request->user()->companies()->findOrFail($request->company_id);

        if ($threshold->company_id !== $company->id) {
            abort(403);
        }

        $this->settingService->deleteCompanyThreshold($threshold);

        return response()->noContent();
    }
}
