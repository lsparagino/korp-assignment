<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmTwoFactorRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function store(TwoFactorAuthenticationRequest $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable($request->user());

        return response()->json(['message' => __('messages.two_factor_enabled')]);
    }

    public function confirm(ConfirmTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $this->authService->confirmTwoFactor($request->user(), $request->code, $confirm);

        return response()->json(['message' => __('messages.two_factor_confirmed')]);
    }

    public function destroy(TwoFactorAuthenticationRequest $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $this->authService->disableTwoFactor($request->user(), $disable);

        return response()->json(['message' => __('messages.two_factor_disabled')]);
    }

    public function qrCode(TwoFactorAuthenticationRequest $request): JsonResponse
    {
        $this->ensureTwoFactorEnabled($request->user());

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'url' => $request->user()->twoFactorQrCodeUrl(),
        ]);
    }

    public function recoveryCodes(TwoFactorAuthenticationRequest $request): JsonResponse
    {
        $this->ensureTwoFactorEnabled($request->user());

        return response()->json(
            collect($request->user()->recoveryCodes())->map(fn ($code) => ['code' => $code])
        );
    }

    public function regenerateRecoveryCodes(TwoFactorAuthenticationRequest $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $generate($request->user());

        return response()->json(['message' => __('messages.recovery_codes_regenerated')]);
    }

    private function ensureTwoFactorEnabled(User $user): void
    {
        if ($user->two_factor_secret === null) {
            abort(400, __('messages.two_factor_not_enabled'));
        }
    }
}
