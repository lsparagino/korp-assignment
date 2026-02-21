<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function store(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable($request->user());

        return response()->json(['message' => __('messages.two_factor_enabled')]);
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $confirm($request->user(), $request->code);

        return response()->json(['message' => __('messages.two_factor_confirmed')]);
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $disable($request->user());

        return response()->json(['message' => __('messages.two_factor_disabled')]);
    }

    public function qrCode(Request $request): JsonResponse
    {
        if (is_null($request->user()->two_factor_secret)) {
            return response()->json(['message' => __('messages.two_factor_not_enabled')], 400);
        }

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'url' => $request->user()->twoFactorQrCodeUrl(),
        ]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        if (is_null($request->user()->two_factor_recovery_codes)) {
            return response()->json(['message' => __('messages.two_factor_not_enabled')], 400);
        }

        return response()->json(
            collect($request->user()->recoveryCodes())->map(fn ($code) => ['code' => $code])
        );
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $generate($request->user());

        return response()->json(['message' => __('messages.recovery_codes_regenerated')]);
    }
}
