<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Request\Models\InternalRequest;

final class RequestCollaborationQuery
{
    public function findVisible(string $publicId, mixed $user): InternalRequest
    {
        $query = InternalRequest::query()->select(['id', 'public_id', 'requester_id', 'status', 'lock_version', 'archived_at']);
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

    public function comments(InternalRequest $request, int $perPage = 10): LengthAwarePaginator
    {
        return $request->comments()->orderByDesc('created_at')->orderByDesc('id')->paginate(min(25, max(5, $perPage)), pageName: 'commentsPage');
    }

    public function attachments(InternalRequest $request): array
    {
        return $request->attachments()->whereNull('removed_at')->latest('created_at')->limit((int) config('request.files.max_count', 20))->get()->all();
    }

    public function audit(InternalRequest $request, int $perPage = 10): LengthAwarePaginator
    {
        return $request->auditEvents()->select(['id', 'public_id', 'request_instance_id', 'aggregate_type', 'aggregate_public_id', 'event_key', 'actor_user_id', 'correlation_id', 'occurred_at'])->orderByDesc('occurred_at')->orderByDesc('id')->paginate(min(25, max(5, $perPage)), pageName: 'auditPage');
    }
}
