<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 10), 500);
        $companyId = $request->input('company_id');

        $walletIds = Wallet::scopedToUser($request->user(), $companyId)->pluck('id');

        $filters = $request->only([
            'type', 'date_from', 'date_to',
            'amount_min', 'amount_max', 'reference',
            'from_wallet_id', 'to_wallet_id',
        ]);

        $transactions = $this->transactionService->getFilteredTransactions($walletIds, $filters, $perPage);

        return TransactionResource::collection($transactions);
    }
}
