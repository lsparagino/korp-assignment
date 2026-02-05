<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 10);
        $perPage = min((int) $perPage, 500);

        // Determine which wallets the user can see transactions for
        if ($request->user()->isAdmin()) {
            $walletIds = $request->user()->wallets()->pluck('id');
        } else {
            $walletIds = $request->user()->assignedWallets()->pluck('wallets.id');
        }

        // Get transactions for those wallets
        $query = \App\Models\Transaction::query()
            ->where(function ($q) use ($walletIds) {
                $q->whereIn('from_wallet_id', $walletIds)
                  ->orWhereIn('to_wallet_id', $walletIds);
            });

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

        if ($request->filled('from_wallet_id')) {
            $query->where('from_wallet_id', $request->from_wallet_id);
        }

        if ($request->filled('to_wallet_id')) {
            $query->where('to_wallet_id', $request->to_wallet_id);
        }

        $transactions = $query->with(['fromWallet', 'toWallet'])
            ->latest()
            ->paginate($perPage);

        return \App\Http\Resources\TransactionResource::collection($transactions);
    }
}
