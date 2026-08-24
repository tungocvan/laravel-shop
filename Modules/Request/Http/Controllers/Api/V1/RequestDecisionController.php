<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Services\DecideRequestTask;

final class RequestDecisionController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, ApproverInboxQuery $query, DecideRequestTask $service): JsonResponse
    {
        $validated = $request->validate(['decision' => ['required', 'in:approve'], 'expected_request_version' => ['required', 'integer', 'min:1'], 'expected_task_version' => ['required', 'integer', 'min:1']]);
        $task = $query->findActionable($publicId, (int) $request->user()->getAuthIdentifier());
        Gate::forUser($request->user())->authorize('decide', $task);
        $decision = $service->approve($task, (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_request_version'], (int) $validated['expected_task_version'], (string) $request->header('Idempotency-Key', ''));
        $instance = $decision->task()->with('run.requestInstance')->firstOrFail()->run->requestInstance;

        return response()->json(['data' => ['public_id' => $decision->public_id, 'decision' => $decision->decision->value, 'request' => ['public_id' => $instance->public_id, 'status' => $instance->status->value, 'lock_version' => $instance->lock_version]]]);
    }
}
