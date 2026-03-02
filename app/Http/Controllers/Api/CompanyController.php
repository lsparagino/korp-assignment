<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyController extends Controller
{
    public function __construct(private CompanyService $companyService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return JsonResource::collection($this->companyService->listForUser($request->user()));
    }

    public function currencies(): JsonResponse
    {
        return response()->json(
            $this->companyService->availableCurrencies()
        );
    }
}
