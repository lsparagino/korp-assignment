<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $user = User::where('invitation_token', $token)->firstOrFail();

        return response()->json([
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $user = User::where('invitation_token', $token)->firstOrFail();

        $accessToken = DB::transaction(function () use ($user, $request) {
            $user->update([
                'password' => Hash::make($request->password),
                'invitation_token' => null,
                'email_verified_at' => now(),
            ]);

            return $user->createToken('auth_token')->plainTextToken;
        });

        return response()->json([
            'message' => 'Account activated successfully',
            'user' => $user,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
        ]);
    }
}
