<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function store(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user());

        return response()->json(['message' => '2FA enabled, please confirm']);
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $confirm($request->user(), $request->code);

        return response()->json(['message' => '2FA confirmed']);
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable($request->user());

        return response()->json(['message' => '2FA disabled']);
    }

    public function qrCode(Request $request)
    {
        if (is_null($request->user()->two_factor_secret)) {
            return response()->json(['message' => '2FA not enabled'], 400);
        }

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'url' => $request->user()->twoFactorQrCodeUrl(),
        ]);
    }

    public function recoveryCodes(Request $request)
    {
        if (is_null($request->user()->two_factor_recovery_codes)) {
            return response()->json(['message' => '2FA not enabled'], 400);
        }

        return response()->json(
            collect($request->user()->recoveryCodes())->map(fn($code) => ['code' => $code])
        );
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        $generate($request->user());

        return response()->json(['message' => 'Recovery codes regenerated']);
    }
}
