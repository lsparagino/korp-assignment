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

        // Get transactions for all wallets owned by the user
        $query = \App\Models\Transaction::query()
            ->where(function ($q) use ($request) {
                $q->whereIn('from_wallet_id', $request->user()->wallets()->pluck('id'))
                  ->orWhereIn('to_wallet_id', $request->user()->wallets()->pluck('id'));
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

        $transactions = $query->with(['fromWallet', 'toWallet'])
            ->latest()
            ->paginate($perPage);

        return \App\Http\Resources\TransactionResource::collection($transactions);
    }
}
