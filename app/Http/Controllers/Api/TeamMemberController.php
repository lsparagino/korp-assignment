<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\User;
use App\Policies\TeamMemberPolicy;
use App\Services\TeamMemberService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TeamMemberService $teamMemberService) {}

    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        Gate::allowIf(fn (User $user) => app(TeamMemberPolicy::class)->viewAny($user));

        $companyId = $request->input('company_id');

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
            $request->input('company_id'),
            $request->wallets ?? []
        );

        return response()->json(['message' => 'Member invited successfully', 'user' => $user], 201);
    }

    public function update(UpdateTeamMemberRequest $request, User $teamMember): JsonResponse
    {
        Gate::allowIf(fn (User $user) => app(TeamMemberPolicy::class)->update($user, $teamMember));

        $this->teamMemberService->update(
            $teamMember,
            $request->name,
            $request->email,
            $request->wallets ?? []
        );

        return response()->json(['message' => 'Member updated successfully', 'user' => $teamMember]);
    }

    public function destroy(User $teamMember): Response
    {
        Gate::allowIf(fn (User $user) => app(TeamMemberPolicy::class)->delete($user, $teamMember));

        $this->teamMemberService->delete($teamMember);

        return response()->noContent();
    }
}
