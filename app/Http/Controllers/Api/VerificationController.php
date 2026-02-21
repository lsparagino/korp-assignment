<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private VerificationService $verificationService) {}

    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail() && ! $request->user()->pending_email) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent']);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $result = $this->verificationService->verifyEmail($request, $id, $hash);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
