<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Request\Models\InternalRequest;

final class MyRequestsQuery
{
    public function paginate(int $userId, string $search, string $status, int $perPage, string $workspace = 'all'): LengthAwarePaginator
    {
        return $this->baseQuery($userId)
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('request_number', 'like', '%'.$search.'%')->orWhere('title_snapshot', 'like', '%'.$search.'%')))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($status === '', fn (Builder $query) => $this->applyWorkspace($query, $workspace))
            ->latest('updated_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function workspaceCounts(int $userId): array
    {
        $counts = $this->baseQuery($userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            'draft' => (int) ($counts['draft'] ?? 0),
            'processing' => (int) ($counts['pending'] ?? 0),
            'returned' => (int) ($counts['returned'] ?? 0),
            'completed' => (int) collect(['approved', 'rejected', 'cancelled'])->sum(fn (string $status): int => (int) ($counts[$status] ?? 0)),
        ];
    }

    public function findVisible(string $publicId, mixed $user): InternalRequest
    {
        $query = InternalRequest::query()->with([
            'type:id,public_id,name',
            'typeVersion:id,public_id,title,form_schema_json,schema_version',
            'latestPayloadRevision',
            'runs' => fn ($runs) => $runs->orderByDesc('sequence_number'),
            'runs.payloadRevision',
            'runs.tasks' => fn ($tasks) => $tasks->orderBy('stage_position')->orderBy('id'),
            'runs.tasks.decision',
            'runs.tasks.candidates',
            'currentRun.tasks',
        ]);
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

    private function baseQuery(int $userId): Builder
    {
        return InternalRequest::query()
            ->select(['id', 'public_id', 'request_number', 'request_type_id', 'requester_id', 'status', 'title_snapshot', 'lock_version', 'updated_at'])
            ->with('type:id,public_id,name')
            ->where('requester_id', $userId);
    }

    private function applyWorkspace(Builder $query, string $workspace): Builder
    {
        return match ($workspace) {
            'draft' => $query->where('status', 'draft'),
            'processing' => $query->where('status', 'pending'),
            'returned' => $query->where('status', 'returned'),
            'completed' => $query->whereIn('status', ['approved', 'rejected', 'cancelled']),
            default => $query,
        };
    }
}
