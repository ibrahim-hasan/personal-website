<?php

namespace App\Services\EditorialApi;

use App\Models\EditorialApiIdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Idempotency
{
    /** @param Closure(): JsonResponse $callback */
    public function execute(Request $request, Closure $callback): JsonResponse
    {
        $key = $this->key($request);

        $clientId = (string) $request->attributes->get('editorial_api_client')->getKey();
        $requestHash = $this->requestHash($request);

        return DB::transaction(function () use ($callback, $clientId, $key, $request, $requestHash): JsonResponse {
            EditorialApiIdempotencyKey::query()
                ->where('client_id', $clientId)
                ->where('idempotency_key', $key)
                ->where('method', $request->method())
                ->where('path', $request->path())
                ->where('expires_at', '<=', now())
                ->delete();

            $existing = EditorialApiIdempotencyKey::query()
                ->where('client_id', $clientId)
                ->where('idempotency_key', $key)
                ->where('method', $request->method())
                ->where('path', $request->path())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (! hash_equals($existing->request_hash, $requestHash)) {
                    throw ValidationException::withMessages(['Idempotency-Key' => ['This key was already used with different input.']]);
                }

                return response()->json($existing->response_body, $existing->response_status)
                    ->header('Idempotent-Replay', 'true');
            }

            $response = $callback();
            $body = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

            EditorialApiIdempotencyKey::query()->create([
                'client_id' => $clientId,
                'idempotency_key' => $key,
                'method' => $request->method(),
                'path' => $request->path(),
                'request_hash' => $requestHash,
                'response_status' => $response->getStatusCode(),
                'response_body' => $body,
                'expires_at' => now()->addHours((int) config('editorial-api.idempotency_ttl_hours')),
            ]);

            return $response;
        });
    }

    private function requestHash(Request $request): string
    {
        $payload = $request->except(['image']);
        $image = $request->file('image');

        if ($image !== null) {
            $payload['_image'] = [
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
                'sha256' => hash_file('sha256', $image->getRealPath()),
            ];
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function replay(Request $request): ?JsonResponse
    {
        $key = $this->key($request);
        $clientId = (string) $request->attributes->get('editorial_api_client')->getKey();
        $existing = EditorialApiIdempotencyKey::query()
            ->where('client_id', $clientId)
            ->where('idempotency_key', $key)
            ->where('method', $request->method())
            ->where('path', $request->path())
            ->where('expires_at', '>', now())
            ->first();

        if ($existing === null) {
            return null;
        }

        if (! hash_equals($existing->request_hash, $this->requestHash($request))) {
            throw ValidationException::withMessages(['Idempotency-Key' => ['This key was already used with different input.']]);
        }

        $response = response()->json($existing->response_body, $existing->response_status)
            ->header('Idempotent-Replay', 'true');
        $revision = data_get($existing->response_body, 'data.revision');

        if (is_int($revision)) {
            $response->header('ETag', '"'.$revision.'"');
        }

        return $response;
    }

    private function key(Request $request): string
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || $key === '' || mb_strlen($key) > 255) {
            throw ValidationException::withMessages(['Idempotency-Key' => ['An idempotency key is required.']]);
        }

        return $key;
    }
}
