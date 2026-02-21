<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request, DashboardService $dashboardService): JsonResponse
    {
        $data = $dashboardService->getDashboardData($request->user(), $request->input('company_id'));

        return response()->json($data);
    }
}
