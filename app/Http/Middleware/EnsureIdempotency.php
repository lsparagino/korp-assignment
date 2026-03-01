<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! Str::isUuid($key)) {
            return response()->json([
                'message' => __('messages.idempotency_key_required'),
            ], 400);
        }

        $userId = $request->user()->id;

        $existing = IdempotencyKey::where('key', $key)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return new JsonResponse($existing->response_body, $existing->response_code);
        }

        $response = $next($request);

        DB::transaction(function () use ($key, $userId, $response) {
            IdempotencyKey::create([
                'key' => $key,
                'user_id' => $userId,
                'response_code' => $response->getStatusCode(),
                'response_body' => json_decode($response->getContent(), true),
            ]);
        });

        return $response;
    }
}
