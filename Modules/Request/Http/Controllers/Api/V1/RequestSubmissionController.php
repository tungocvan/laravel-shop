<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\SubmitInternalRequest;

final class RequestSubmissionController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $httpRequest, MyRequestsQuery $query, SubmitInternalRequest $service): JsonResponse
    {
        $validated = $httpRequest->validate(['expected_version' => ['required', 'integer', 'min:1'], 'payload' => ['nullable', 'array']]);
        $idempotencyKey = (string) $httpRequest->header('Idempotency-Key', '');
        $request = $query->findVisible($publicId, $httpRequest->user());
        Gate::forUser($httpRequest->user())->authorize('submit', $request);
        $submitted = $service->handle($request, (int) $httpRequest->user()->getAuthIdentifier(), (int) $validated['expected_version'], $idempotencyKey, $validated['payload'] ?? null);

        return response()->json(['data' => ['public_id' => $submitted->public_id, 'request_number' => $submitted->request_number, 'status' => $submitted->status->value, 'lock_version' => $submitted->lock_version]]);
    }
}
