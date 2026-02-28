<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitiateTransferRequest;
use App\Http\Requests\Api\ReviewTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

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
}
