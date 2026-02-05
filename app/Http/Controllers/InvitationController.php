<?php

namespace App\Http\Controllers;

class InvitationController extends Controller
{
    public function show($token)
    {
        $user = \App\Models\User::where('invitation_token', $token)->firstOrFail();

        return response()->json([
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function store(\Illuminate\Http\Request $request, $token)
    {
        $user = \App\Models\User::where('invitation_token', $token)->firstOrFail();

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'invitation_token' => null,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
