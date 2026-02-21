<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\User;
use App\Services\TeamMemberService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TeamMemberController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TeamMemberService $teamMemberService) {}

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $companyId = $request->company_id;

        if (! $companyId) {
            return response()->json([
                'company' => config('app.name'),
                'data' => [],
                'meta' => [],
            ]);
        }

        return TeamMemberResource::collection(
            $this->teamMemberService->list($companyId)
        )->additional([
            'company' => config('app.name'),
        ]);
    }

    public function store(StoreTeamMemberRequest $request): JsonResponse
    {
        $user = $this->teamMemberService->invite(
            $request->name,
            $request->email,
            $request->company_id,
            $request->wallets ?? []
        );

        return response()->json(['message' => __('messages.member_invited'), 'user' => $user], 201);
    }

    public function update(UpdateTeamMemberRequest $request, User $teamMember): JsonResponse
    {
        $this->authorize('update', $teamMember);

        $this->teamMemberService->update(
            $teamMember,
            $request->name,
            $request->email,
            $request->wallets ?? []
        );

        return response()->json(['message' => __('messages.member_updated'), 'user' => $teamMember]);
    }

    public function destroy(User $teamMember): Response
    {
        $this->authorize('delete', $teamMember);

        $this->teamMemberService->delete($teamMember);

        return response()->noContent();
    }
}
