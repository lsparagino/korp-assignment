<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class TestingController
{
    public function resetDatabase(): JsonResponse
    {
        Artisan::call('migrate:fresh', ['--seed' => true]);

        return response()->json(['message' => 'Database reset successfully']);
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
}
