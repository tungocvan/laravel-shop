<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\ResubmitInternalRequest;

final class RequestResubmissionController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, MyRequestsQuery $query, ResubmitInternalRequest $service): JsonResponse
    {
        $validated = $request->validate(['expected_version' => ['required', 'integer', 'min:1'], 'payload' => ['required', 'array']]);
        $instance = $query->findVisible($publicId, $request->user());
        Gate::forUser($request->user())->authorize('submit', $instance);
        $instance = $service->handle($instance, $validated['payload'], (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_version'], (string) $request->header('Idempotency-Key', ''));

        return response()->json(['data' => ['public_id' => $instance->public_id, 'status' => $instance->status->value, 'lock_version' => $instance->lock_version]]);
    }
}
