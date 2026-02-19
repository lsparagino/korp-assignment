<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\User;
use App\Services\TeamMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamMemberController extends Controller
{
    public function __construct(private TeamMemberService $teamMemberService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $companyId = $request->input('company_id');

        if (! $companyId) {
            return response()->json([
                'company' => config('app.name'),
                'members' => [],
                'pagination' => [],
            ]);
        }

        $users = User::query()
            ->whereHas('companies', function ($q) use ($companyId) {
                $q->where('companies.id', $companyId);
            })
            ->with(['assignedWallets' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->withCount(['assignedWallets' => function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            }])
            ->orderBy('name')
            ->paginate(10);

        return TeamMemberResource::collection($users)->additional([
            'company' => config('app.name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamMemberRequest $request): JsonResponse
    {
        $companyId = $request->input('company_id');
        if (! $companyId || ! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'Unauthorized access to company.');
        }

        $user = $this->teamMemberService->invite(
            $request->name,
            $request->email,
            $companyId,
            $request->wallets ?? []
        );

        return response()->json(['message' => 'Member invited successfully', 'user' => $user], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamMemberRequest $request, User $team_member): JsonResponse
    {
        if ($team_member->role !== UserRole::Member) {
            return response()->json(['message' => 'Only members can be edited'], 403);
        }

        $this->teamMemberService->update(
            $team_member,
            $request->name,
            $request->email,
            $request->wallets ?? []
        );

        return response()->json(['message' => 'Member updated successfully', 'user' => $team_member]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $team_member): JsonResponse
    {
        if ($team_member->role !== UserRole::Member) {
            return response()->json(['message' => 'Only members can be deleted'], 403);
        }

        $this->teamMemberService->delete($team_member);

        return response()->json(['message' => 'Member deleted successfully']);
    }
}
