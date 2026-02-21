<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitationService) {}

    public function show(string $token): JsonResponse
    {
        $user = User::findByInvitationTokenOrFail($token);

        return response()->json([
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $result = $this->invitationService->acceptInvitation($token, $request->password);

        return response()->json($result);
    }
}
