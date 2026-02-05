<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->input('company_id');

        if (! $companyId) {
            return response()->json([
                'company' => config('app.name'),
                'members' => [],
                'pagination' => [],
            ]);
        }

        // Ensure current user belongs to company
        if (! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
            abort(403);
        }

        $users = \App\Models\User::query()
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

        return response()->json([
            'company' => config('app.name'),
            'members' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'wallet_access' => $user->role === \App\Enums\UserRole::Admin ? 'Full Access' : ($user->assigned_wallets_count.' Wallets'),
                    'is_pending' => $user->is_pending,
                    'is_current' => $user->id === auth()->id(),
                    'assigned_wallets' => $user->assignedWallets->pluck('id'),
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreTeamMemberRequest $request)
    {
        $companyId = $request->input('company_id');
        if (! $companyId || ! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'Unauthorized access to company.');
        }

        $token = \Illuminate\Support\Str::random(64);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => \App\Enums\UserRole::Member,
            'invitation_token' => $token,
            'invited_at' => now(),
        ]);

        $user->companies()->attach($companyId);

        if ($request->has('wallets')) {
            $user->assignedWallets()->sync($request->wallets);
        }

        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TeamMemberInvitation($user));

        return response()->json(['message' => 'Member invited successfully', 'user' => $user], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\UpdateTeamMemberRequest $request, \App\Models\User $team_member)
    {
        if ($team_member->role !== \App\Enums\UserRole::Member) {
            return response()->json(['message' => 'Only members can be edited'], 403);
        }

        $team_member->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->has('wallets')) {
            $team_member->assignedWallets()->sync($request->wallets);
        }

        return response()->json(['message' => 'Member updated successfully', 'user' => $team_member]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\User $team_member)
    {
        if ($team_member->role !== \App\Enums\UserRole::Member) {
            return response()->json(['message' => 'Only members can be deleted'], 403);
        }

        $team_member->delete();

        return response()->json(['message' => 'Member deleted successfully']);
    }
}
