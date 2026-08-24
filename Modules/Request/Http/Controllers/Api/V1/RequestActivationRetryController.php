<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\RetryStageActivation;

final class RequestActivationRetryController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, MyRequestsQuery $query, RetryStageActivation $service): JsonResponse
    {
        $validated = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);
        $instance = $query->findVisible($publicId, $request->user());
        Gate::forUser($request->user())->authorize('retryActivation', $instance);
        $run = $service->handle($instance, (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_version'], (string) $request->header('Idempotency-Key', ''));

        return response()->json(['data' => ['public_id' => $run->public_id, 'status' => $run->status->value, 'lock_version' => $run->lock_version]]);
    }
}
