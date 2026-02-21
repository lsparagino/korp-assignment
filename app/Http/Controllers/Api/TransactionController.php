<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FilterTransactionsRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(FilterTransactionsRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $companyId = $request->input('company_id');

        $walletIds = Wallet::scopedToUser($request->user(), $companyId)->pluck('id');

        $transactions = $this->transactionService->getFilteredTransactions(
            $walletIds,
            $request->validated(),
            $perPage
        );

        return TransactionResource::collection($transactions);
    }
}
