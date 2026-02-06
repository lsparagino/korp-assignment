<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([Fortify::email() => 'required|email']);

        $status = Password::broker(config('fortify.passwords'))->sendResetLink(
            $request->only(Fortify::email())
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }

    public function resetPassword(Request $request, ResetsUserPasswords $resets)
    {
        $request->validate([
            'token' => 'required',
            Fortify::email() => 'required|email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker(config('fortify.passwords'))->reset(
            $request->only(Fortify::email(), 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($resets, $request) {
                $resets->reset($user, $request->only('password', 'password_confirmation'));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => trans($status)])
            : response()->json(['message' => trans($status)], 422);
    }
}
