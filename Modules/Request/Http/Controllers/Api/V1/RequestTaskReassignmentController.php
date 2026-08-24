<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Services\ReassignRequestTask;
use Modules\Request\Models\RequestTask;

final class RequestTaskReassignmentController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, ReassignRequestTask $service): JsonResponse
    {
        $validated = $request->validate(['target_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000'], 'expected_request_version' => ['required', 'integer', 'min:1'], 'expected_task_version' => ['required', 'integer', 'min:1']]);
        $task = RequestTask::query()->where('public_id', $publicId)->firstOrFail();
        Gate::forUser($request->user())->authorize('reassign', $task);
        $replacement = $service->handle($task, (int) $validated['target_user_id'], $validated['reason'], (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_request_version'], (int) $validated['expected_task_version'], (string) $request->header('Idempotency-Key', ''));

        return response()->json(['data' => ['public_id' => $replacement->public_id, 'status' => $replacement->status->value, 'lock_version' => $replacement->lock_version]]);
    }
}
