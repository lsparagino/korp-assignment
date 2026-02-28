<?php

namespace App\Http\Controllers\Api;

use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return JsonResource::collection($request->user()->companies);
    }

    public function currencies(): JsonResponse
    {
        return response()->json(
            array_column(WalletCurrency::cases(), 'value')
        );
    }
}
