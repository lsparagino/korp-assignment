<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWalletRequest;
use App\Http\Requests\Api\UpdateWalletRequest;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Wallet::class);

        $perPage = min((int) $request->input('per_page', 10), 500);
        $companyId = $request->input('company_id');

        if (! $companyId) {
            return WalletResource::collection(collect());
        }

        $query = Wallet::scopedToUser($request->user(), $companyId);

        return WalletResource::collection(
            $query->latest()->paginate($perPage)
        );
    }

    public function store(StoreWalletRequest $request): WalletResource
    {
        $this->authorize('create', Wallet::class);

        $wallet = $request->user()->wallets()->create([
            ...$request->safe()->only(['name', 'currency']),
            'status' => \App\Enums\WalletStatus::Active,
            'company_id' => $request->company_id,
        ]);

        Log::info('Wallet created', ['wallet_id' => $wallet->id, 'user_id' => $request->user()->id]);

        return new WalletResource($wallet);
    }

    public function show(Wallet $wallet): WalletResource
    {
        $this->authorize('view', $wallet);

        return new WalletResource($wallet);
    }

    public function toggleFreeze(Wallet $wallet): WalletResource
    {
        $this->authorize('update', $wallet);

        $wallet->update([
            'status' => $wallet->status === \App\Enums\WalletStatus::Active
                ? \App\Enums\WalletStatus::Frozen
                : \App\Enums\WalletStatus::Active,
        ]);

        return new WalletResource($wallet);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): WalletResource
    {
        $this->authorize('update', $wallet);

        $wallet->update($request->validated());

        return new WalletResource($wallet);
    }

    public function destroy(Wallet $wallet): Response
    {
        $this->authorize('delete', $wallet);

        $wallet->delete();

        Log::info('Wallet deleted', ['wallet_id' => $wallet->id]);

        return response()->noContent();
    }
}
