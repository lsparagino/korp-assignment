<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitiateTransferRequest;
use App\Http\Requests\Api\ReviewTransferRequest;
use App\Models\Transaction;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransferController extends Controller
{
    public function __construct(private TransferService $transferService) {}

    public function store(InitiateTransferRequest $request): JsonResponse
    {
        $result = $this->transferService->initiateTransfer(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => __('messages.transfer_initiated'),
            'data' => $result,
        ], 201);
    }

    public function review(ReviewTransferRequest $request, string $groupId): JsonResponse
    {
        $result = $this->transferService->reviewTransfer(
            $request->user(),
            $groupId,
            $request->validated()['action'],
            $request->validated()['reason'] ?? null
        );

        return response()->json([
            'message' => __('messages.transfer_reviewed'),
            'data' => $result,
        ]);
    }

    public function cancel(Request $request, string $groupId): JsonResponse
    {
        $transaction = Transaction::where('group_id', $groupId)->firstOrFail();

        Gate::authorize('cancel', $transaction);

        $result = $this->transferService->cancelTransfer(
            $request->user(),
            $groupId
        );

        return response()->json([
            'message' => __('messages.transfer_cancelled'),
            'data' => $result,
        ]);
    }
}
