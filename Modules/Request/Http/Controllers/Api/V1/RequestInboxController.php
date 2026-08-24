<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Models\RequestTask;

final class RequestInboxController extends Controller
{
    public function __invoke(HttpRequest $request, ApproverInboxQuery $query): JsonResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', RequestTask::class);
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $tasks = $query->paginate((int) $request->user()->getAuthIdentifier(), trim((string) ($validated['search'] ?? '')), (int) ($validated['per_page'] ?? 25));

        return response()->json(['data' => collect($tasks->items())->map(fn ($task): array => ['public_id' => $task->public_id, 'stage' => $task->stage_name_snapshot, 'stage_position' => $task->stage_position, 'lock_version' => $task->lock_version, 'request' => ['public_id' => $task->run->requestInstance->public_id, 'number' => $task->run->requestInstance->request_number, 'title' => $task->run->requestInstance->title_snapshot, 'lock_version' => $task->run->requestInstance->lock_version]])->all(), 'meta' => ['current_page' => $tasks->currentPage(), 'last_page' => $tasks->lastPage(), 'per_page' => $tasks->perPage(), 'total' => $tasks->total()]]);
    }
}
