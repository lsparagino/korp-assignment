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
        $result = $this->verificationService->resendVerification($request->user());

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $result = $this->verificationService->verifyEmail($request, $id, $hash);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
