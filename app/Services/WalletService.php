<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
use App\Enums\WalletStatus;
use App\Http\Resources\WalletResource;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function __construct(private AuditService $auditService) {}

    public function listForUser(User $user, ?int $companyId, int $perPage): AnonymousResourceCollection
    {
        if (! $companyId) {
            return WalletResource::collection(collect());
        }

        $wallets = Wallet::scopedToUser($user, $companyId)
            ->withExists('transactions')
            ->withBalance()
            ->latest()
            ->paginate($perPage);

        return WalletResource::collection($wallets);
    }

    public function create(User $user, array $data): Wallet
    {
        $wallet = $user->wallets()->create([
            'name' => $data['name'],
            'currency' => $data['currency'],
            'status' => WalletStatus::Active,
            'company_id' => $data['company_id'],
        ]);

        Log::info('Wallet created', ['wallet_id' => $wallet->id, 'user_id' => $user->id]);

        $this->auditService->log(
            AuditCategory::Wallet,
            AuditSeverity::Normal,
            'wallet.created',
            __('messages.audit.wallet_created'),
            ['metadata' => ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name]],
        );

        return $wallet;
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        $changes = [];
        foreach ($data as $key => $value) {
            if ($wallet->getAttribute($key) != $value) {
                $changes[$key] = ['from' => $wallet->getAttribute($key), 'to' => $value];
            }
        }

        $wallet->update($data);

        $this->auditService->log(
            AuditCategory::Wallet,
            AuditSeverity::Normal,
            'wallet.updated',
            __('messages.audit.wallet_updated'),
            ['metadata' => ['wallet_id' => $wallet->id, 'wallet_name' => $wallet->name, 'changes' => $changes]],
        );

        return $wallet;
    }

    public function delete(Wallet $wallet): void
    {
        $walletId = $wallet->id;
        $walletName = $wallet->name;

        $wallet->delete();

        $this->auditService->log(
            AuditCategory::Wallet,
            AuditSeverity::Normal,
            'wallet.deleted',
            __('messages.audit.wallet_deleted'),
            ['metadata' => ['wallet_id' => $walletId, 'wallet_name' => $walletName]],
        );
    }

    public function toggleFreeze(Wallet $wallet): Wallet
    {
        $previousStatus = $wallet->status->value;
        $wallet->toggleFreeze();

        $this->auditService->log(
            AuditCategory::Wallet,
            AuditSeverity::Medium,
            'wallet.freeze_toggled',
            __('messages.audit.wallet_freeze_toggled'),
            ['metadata' => [
                'wallet_id' => $wallet->id,
                'wallet_name' => $wallet->name,
                'changes' => [
                    'status' => [
                        'from' => $previousStatus,
                        'to' => $wallet->status->value,
                    ],
                ],
            ]],
        );

        return $wallet;
    }

    public function getBalancesByCurrency(Collection $wallets): Collection
    {
        return $wallets->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group) => $group->sum(fn ($w) => $w->balance))
            ->map(fn ($total, $currency) => ['currency' => $currency, 'amount' => $total])
            ->values();
    }

    public function getTopWallets(Collection $wallets, int $limit = 3): Collection
    {
        return $wallets->sortByDesc(fn ($w) => $w->balance)
            ->take($limit)
            ->map(fn ($w) => [
                'name' => $w->name,
                'balance' => $w->balance,
                'currency' => $w->currency->value,
            ])->values();
    }

    public function getOthersAggregation(Collection $wallets, int $topLimit = 3): Collection
    {
        $others = $wallets->sortByDesc(fn ($w) => $w->balance)->slice($topLimit);

        if ($others->isEmpty()) {
            return collect();
        }

        return $others->groupBy(fn ($w) => $w->currency->value)
            ->map(fn ($group, $currency) => [
                'currency' => $currency,
                'amount' => $group->sum(fn ($w) => $w->balance),
            ])->values();
    }
}
