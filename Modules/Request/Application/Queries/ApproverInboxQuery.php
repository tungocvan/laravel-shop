<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestTask;

final class ApproverInboxQuery
{
    public function paginate(int $userId, string $search, int $perPage, string $view = 'pending', string $decision = 'all'): LengthAwarePaginator
    {
        $processedStatuses = [TaskStatus::Approved->value, TaskStatus::Rejected->value, TaskStatus::Returned->value];

        return $this->baseQuery($userId)
            ->when($view === 'pending', fn (Builder $query) => $this->scopePending($query))
            ->when($view === 'processed', fn ($query) => $query->whereIn('status', $processedStatuses))
            ->when($view === 'all', fn ($query) => $query->whereIn('status', [TaskStatus::Active->value, ...$processedStatuses]))
            ->when($view === 'processed' && in_array($decision, $processedStatuses, true), fn ($query) => $query->where('status', $decision))
            ->when($search !== '', fn ($query) => $query->whereHas('run.requestInstance', fn ($request) => $request->where(fn ($nested) => $nested
                ->where('request_number', 'like', '%'.$search.'%')
                ->orWhere('title_snapshot', 'like', '%'.$search.'%'))))
            ->when($view === 'pending', fn ($query) => $query
                ->orderByRaw('CASE WHEN suspended_at IS NOT NULL THEN 0 WHEN due_at IS NOT NULL AND due_at <= ? THEN 1 WHEN warning_at IS NOT NULL AND warning_at <= ? THEN 2 ELSE 3 END', [now('UTC'), now('UTC')])
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->oldest('due_at')
                ->oldest('activated_at')
                ->oldest('id'))
            ->when($view !== 'pending', fn ($query) => $query->orderByDesc('decided_at')->orderByDesc('id'))
            ->paginate($perPage);
    }

    public function workloadSummary(int $userId): array
    {
        $now = now('UTC');
        $query = $this->scopePending(RequestTask::query()->where('assignee_user_id', $userId));

        return [
            'pending' => (clone $query)->count(),
            'overdue' => (clone $query)->whereNotNull('due_at')->where('due_at', '<=', $now)->count(),
            'warning' => (clone $query)->whereNull('suspended_at')->whereNotNull('warning_at')->where('warning_at', '<=', $now)->where(fn ($scope) => $scope->whereNull('due_at')->orWhere('due_at', '>', $now))->count(),
            'suspended' => (clone $query)->whereNotNull('suspended_at')->count(),
        ];
    }

    public function processedSummary(int $userId): array
    {
        $counts = RequestTask::query()
            ->where('assignee_user_id', $userId)
            ->whereIn('status', [TaskStatus::Approved->value, TaskStatus::Rejected->value, TaskStatus::Returned->value])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            'approved' => (int) ($counts[TaskStatus::Approved->value] ?? 0),
            'rejected' => (int) ($counts[TaskStatus::Rejected->value] ?? 0),
            'returned' => (int) ($counts[TaskStatus::Returned->value] ?? 0),
        ];
    }

    public function findActionable(string $publicId, int $userId): RequestTask
    {
        return RequestTask::query()
            ->with(['run.requestInstance', 'candidates'])
            ->where('public_id', $publicId)
            ->where('assignee_user_id', $userId)
            ->where('status', TaskStatus::Active)
            ->whereNull('suspended_at')
            ->whereHas('candidates', fn ($query) => $query->where('user_id', $userId)->where('is_effective', true))
            ->whereHas('run', fn ($query) => $query->where('status', RunStatus::Active)->whereHas('requestInstance', fn ($request) => $request->where('status', RequestStatus::Pending)))
            ->firstOrFail();
    }

    private function baseQuery(int $userId): Builder
    {
        return RequestTask::query()
            ->select([
                'id', 'public_id', 'request_run_id', 'stage_name_snapshot', 'stage_position', 'stage_mode', 'status',
                'assignee_user_id', 'lock_version', 'activated_at', 'warning_at', 'due_at', 'grace_expires_at',
                'overdue_at', 'suspended_at', 'decided_at', 'closed_at',
            ])
            ->with([
                'run:id,request_instance_id,status,current_stage_position',
                'run.requestInstance:id,public_id,request_number,requester_id,status,title_snapshot,submitted_at,approved_at,rejected_at,returned_at,lock_version',
            ])
            ->where('assignee_user_id', $userId);
    }

    private function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status', TaskStatus::Active->value)
            ->whereHas('run', fn ($run) => $run
                ->where('status', RunStatus::Active->value)
                ->whereColumn('current_stage_position', 'request_tasks.stage_position')
                ->whereHas('requestInstance', fn ($request) => $request->where('status', RequestStatus::Pending->value)));
    }
}
