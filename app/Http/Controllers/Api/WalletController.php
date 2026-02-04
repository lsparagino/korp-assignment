<?php

namespace App\Http\Controllers\Api;

use App\Enums\WalletStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WalletController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Wallet::class);

        return WalletResource::collection(
            $request->user()->wallets()->latest()->get()
        );
    }

    public function store(Request $request): WalletResource
    {
        $this->authorize('create', Wallet::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\WalletCurrency::class)],
        ]);

        $wallet = $request->user()->wallets()->create([
            ...$validated,
            'balance' => 0,
            'status' => WalletStatus::Active,
        ]);

        return new WalletResource($wallet);
    }

    public function show(Wallet $wallet): WalletResource
    {
        $this->authorize('view', $wallet);

        return new WalletResource($wallet);
    }

    public function update(Request $request, Wallet $wallet): WalletResource
    {
        $this->authorize('update', $wallet);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\WalletCurrency::class)],
        ]);

        $wallet->update($validated);

        return new WalletResource($wallet);
    }

    public function toggleFreeze(Wallet $wallet): WalletResource
    {
        $this->authorize('update', $wallet);

        $wallet->update([
            'status' => $wallet->status === WalletStatus::Active 
                ? WalletStatus::Frozen 
                : WalletStatus::Active,
        ]);

        return new WalletResource($wallet);
    }

    public function destroy(Wallet $wallet): Response
    {
        $this->authorize('delete', $wallet);

        $wallet->delete();

        return response()->noContent();
    }
}
