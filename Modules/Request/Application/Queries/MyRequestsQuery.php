<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Request\Models\InternalRequest;

final class MyRequestsQuery
{
    public function paginate(int $userId, string $search, string $status, int $perPage): LengthAwarePaginator
    {
        return InternalRequest::query()
            ->select(['id', 'public_id', 'request_number', 'request_type_id', 'requester_id', 'status', 'title_snapshot', 'lock_version', 'updated_at'])
            ->with('type:id,public_id,name')
            ->where('requester_id', $userId)
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('request_number', 'like', '%'.$search.'%')->orWhere('title_snapshot', 'like', '%'.$search.'%')))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('updated_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findVisible(string $publicId, mixed $user): InternalRequest
    {
        $query = InternalRequest::query()->with(['type:id,public_id,name', 'typeVersion:id,public_id,title,form_schema_json,schema_version', 'latestPayloadRevision']);
        $query->with(['currentRun.tasks' => fn ($tasks) => $tasks->where('assignee_user_id', (int) $user->getAuthIdentifier())]);
        if (! method_exists($user, 'checkPermissionTo') || ! $user->checkPermissionTo('request.instance.view-all', 'admin')) {
            $query->where(function ($scope) use ($user): void {
                $scope->where('requester_id', (int) $user->getAuthIdentifier());
                if (method_exists($user, 'checkPermissionTo') && $user->checkPermissionTo('request.instance.view-participant', 'admin')) {
                    $scope->orWhereHas('runs.tasks', fn ($tasks) => $tasks->where('assignee_user_id', (int) $user->getAuthIdentifier()));
                }
            });
        }

        return $query->where('public_id', $publicId)->firstOrFail();
    }
}
