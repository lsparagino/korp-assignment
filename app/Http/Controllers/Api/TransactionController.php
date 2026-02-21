<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FilterTransactionsRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(FilterTransactionsRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);

        $transactions = $this->transactionService->getFilteredTransactions(
            $request->user(),
            $request->company_id,
            $request->validated(),
            $perPage
        );

        return TransactionResource::collection($transactions);
    }
}
