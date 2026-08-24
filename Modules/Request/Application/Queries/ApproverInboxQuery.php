<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestTask;

final class ApproverInboxQuery
{
    public function paginate(int $userId, string $search, int $perPage): LengthAwarePaginator
    {
        return RequestTask::query()
            ->select(['id', 'public_id', 'request_run_id', 'stage_name_snapshot', 'stage_position', 'stage_mode', 'status', 'assignee_user_id', 'lock_version', 'activated_at'])
            ->with(['run:id,request_instance_id,status,current_stage_position', 'run.requestInstance:id,public_id,request_number,requester_id,status,title_snapshot,submitted_at,lock_version'])
            ->where('assignee_user_id', $userId)
            ->where('status', TaskStatus::Active)
            ->whereHas('run', fn ($query) => $query->where('status', RunStatus::Active)->whereColumn('current_stage_position', 'request_tasks.stage_position')->whereHas('requestInstance', fn ($request) => $request->where('status', RequestStatus::Pending)))
            ->when($search !== '', fn ($query) => $query->whereHas('run.requestInstance', fn ($request) => $request->where(fn ($nested) => $nested->where('request_number', 'like', '%'.$search.'%')->orWhere('title_snapshot', 'like', '%'.$search.'%'))))
            ->oldest('activated_at')
            ->oldest('id')
            ->paginate($perPage);
    }

    public function findActionable(string $publicId, int $userId): RequestTask
    {
        return RequestTask::query()
            ->with(['run.requestInstance', 'candidates'])
            ->where('public_id', $publicId)
            ->where('assignee_user_id', $userId)
            ->where('status', TaskStatus::Active)
            ->whereHas('candidates', fn ($query) => $query->where('user_id', $userId)->where('is_effective', true))
            ->whereHas('run', fn ($query) => $query->where('status', RunStatus::Active)->whereHas('requestInstance', fn ($request) => $request->where('status', RequestStatus::Pending)))
            ->firstOrFail();
    }
}
