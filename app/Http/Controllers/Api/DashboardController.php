<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData($request->user(), $request->company_id);

        return response()->json($data);
    }
}
