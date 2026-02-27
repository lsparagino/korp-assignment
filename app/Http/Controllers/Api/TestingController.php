<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Enums\WalletStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class TestingController
{
    public function resetDatabase(): JsonResponse
    {
        try {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

            return response()->json(['message' => 'Database reset successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Database reset failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'sometimes|string',
            'role' => 'sometimes|string|in:admin,member',
            'company_id' => 'sometimes|integer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'password'),
            'role' => $validated['role'] ?? 'member',
            'email_verified_at' => now(),
        ]);

        if (isset($validated['company_id'])) {
            $user->companies()->attach($validated['company_id']);
        } else {
            $company = Company::first();
            if ($company) {
                $user->companies()->attach($company);
            }
        }

        $token = $user->createToken('e2e-token')->plainTextToken;

        return response()->json([
            'user' => $user->fresh(),
            'token' => $token,
        ]);
    }

    public function loginUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('e2e-token')->plainTextToken;

        $user->load('companies');

        return response()->json([
            'user' => $user,
            'token' => $token,
            'company' => $user->companies->first(),
        ]);
    }

    public function createPasswordResetToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $token = Password::broker()->createToken($user);

        return response()->json([
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    public function createSecondCompany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'company_name' => 'sometimes|string',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $company = Company::create([
            'name' => $validated['company_name'] ?? 'Second Corp',
        ]);
        $user->companies()->attach($company);

        return response()->json([
            'company' => $company,
        ]);
    }

    public function createWallet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'name' => 'sometimes|string',
            'currency' => 'sometimes|string|in:USD,EUR,GBP',
            'balance' => 'sometimes|numeric|min:0',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $company = $user->companies()->first();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'company_id' => $company?->id,
            'name' => $validated['name'] ?? 'Test Wallet',
            'currency' => $validated['currency'] ?? 'USD',
            'status' => WalletStatus::Active,
            'address' => 'bc1q'.Str::lower(Str::random(36)),
        ]);

        // Fund the wallet with an initial credit if balance is requested
        $balance = $validated['balance'] ?? 0;
        if ($balance > 0) {
            \App\Models\Transaction::create([
                'wallet_id' => $wallet->id,
                'counterpart_wallet_id' => null,
                'type' => TransactionType::Credit,
                'amount' => $balance,
                'currency' => $wallet->currency,
                'external' => true,
                'status' => 'completed',
                'reference' => 'Initial funding',
                'group_id' => Str::uuid()->toString(),
            ]);
        }

        return response()->json(['wallet' => $wallet]);
    }
}
