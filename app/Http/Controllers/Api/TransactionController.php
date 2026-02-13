<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 10);
        $perPage = min((int)$perPage, 500);
        $companyId = $request->input('company_id');

        // Wallet scoping using model scope
        $walletIds = Wallet::scopedToUser($request->user(), $companyId)->pluck('id');

        // Base query using transaction scope
        $query = Transaction::forWallets($walletIds);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'LIKE', '%' . $request->reference . '%');
        }

        if ($request->input('from_wallet_id') === 'external') {
            $query->whereNull('from_wallet_id');
        } elseif ($request->filled('from_wallet_id')) {
            $query->where('from_wallet_id', $request->from_wallet_id);
        }

        if ($request->input('to_wallet_id') === 'external') {
            $query->whereNull('to_wallet_id');
        } elseif ($request->filled('to_wallet_id')) {
            $query->where('to_wallet_id', $request->to_wallet_id);
        }

        $transactions = $query->with(['fromWallet', 'toWallet'])
            ->latest()
            ->paginate($perPage);

        return TransactionResource::collection($transactions);
    }
}
