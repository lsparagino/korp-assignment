<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    public function __construct(private AuthService $authService) {}

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
        $result = $this->authService->acceptInvitation($token, $request->password);

        return response()->json($result);
    }
}
