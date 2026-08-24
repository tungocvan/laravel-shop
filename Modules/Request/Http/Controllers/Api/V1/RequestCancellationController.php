<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Domain\Enums\RequestStatus;

final class RequestCancellationController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, MyRequestsQuery $query, CancelInternalRequest $service): JsonResponse
    {
        $validated = $request->validate(['expected_version' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:2000']]);
        $instance = $query->findVisible($publicId, $request->user());
        Gate::forUser($request->user())->authorize('cancel', $instance);
        $cancelAny = $instance->status === RequestStatus::Pending;
        $instance = $service->handle($instance, (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_version'], (string) $request->header('Idempotency-Key', ''), $validated['reason'] ?? null, $cancelAny);

        return response()->json(['data' => ['public_id' => $instance->public_id, 'status' => $instance->status->value, 'lock_version' => $instance->lock_version]]);
    }
}
