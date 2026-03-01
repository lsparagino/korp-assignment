<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FilterAuditLogsRequest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(FilterAuditLogsRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);

        $result = $this->auditService->getFilteredLogs(
            $request->company_id,
            $request->validated(),
            $perPage,
        );

        return response()->json($result);
    }
}
