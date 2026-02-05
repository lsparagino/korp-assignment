<?php

namespace App\Http\Controllers;

class InvitationController extends Controller
{
    public function show($token)
    {
        $user = \App\Models\User::where('invitation_token', $token)->firstOrFail();

        return \Inertia\Inertia::render('auth/accept-invitation', [
            'token' => $token,
            'email' => $user->email,
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

        auth()->login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
