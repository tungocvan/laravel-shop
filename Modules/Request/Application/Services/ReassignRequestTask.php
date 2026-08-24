<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\CandidateSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestTask;
use Modules\User\Contracts\UserDirectory;

final class ReassignRequestTask
{
    public function __construct(private readonly UserDirectory $users, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(RequestTask $task, int $targetUserId, string $reason, int $actorId, int $requestVersion, int $taskVersion, string $idempotencyKey): RequestTask
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => ['reason_required']]);
        }
        $response = DB::transaction(function () use ($task, $targetUserId, $reason, $actorId, $requestVersion, $taskVersion, $idempotencyKey): array {
            $meta = RequestTask::query()->with('run:id,request_instance_id')->findOrFail($task->id);
            $request = InternalRequest::query()->lockForUpdate()->findOrFail($meta->run->request_instance_id);
            $run = RequestRun::query()->lockForUpdate()->findOrFail($meta->request_run_id);
            $tasks = RequestTask::query()->where('request_run_id', $run->id)->where('stage_position', $meta->stage_position)->orderBy('id')->lockForUpdate()->get();
            $locked = $tasks->firstWhere('id', $task->id) ?? throw ValidationException::withMessages(['task' => ['task_not_reassignable']]);

            return $this->idempotency->execute($actorId, 'request.task.reassign', $locked->public_id, $idempotencyKey, compact('targetUserId', 'reason', 'requestVersion', 'taskVersion'), function (string $correlationId, string $keyHash) use ($request, $run, $locked, $targetUserId, $reason, $actorId, $requestVersion, $taskVersion): array {
                $target = $this->users->findActive($targetUserId);
                if (! $target || $targetUserId === $request->requester_id || $request->status !== RequestStatus::Pending || $run->status !== RunStatus::Active || $locked->status !== TaskStatus::Active || $locked->stage_position !== $run->current_stage_position || ! $locked->stageDefinition()->where('allow_reassignment', true)->exists()) {
                    throw ValidationException::withMessages(['task' => ['task_not_reassignable']]);
                }
                if ($request->lock_version !== $requestVersion || $locked->lock_version !== $taskVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                if (RequestTask::query()->where('request_run_id', $run->id)->where('stage_position', $locked->stage_position)->where('assignee_user_id', $targetUserId)->where('status', TaskStatus::Active)->exists()) {
                    throw ValidationException::withMessages(['target_user_id' => ['already_active_candidate']]);
                }
                $now = now('UTC');
                $replacement = RequestTask::query()->create(['request_run_id' => $run->id, 'request_stage_definition_id' => $locked->request_stage_definition_id, 'stage_key_snapshot' => $locked->stage_key_snapshot, 'stage_name_snapshot' => $locked->stage_name_snapshot, 'stage_position' => $locked->stage_position, 'stage_mode' => $locked->stage_mode, 'status' => TaskStatus::Active, 'assignee_user_id' => $targetUserId, 'resolver_key_snapshot' => $locked->resolver_key_snapshot, 'resolver_source_snapshot_json' => ['source' => CandidateSource::Reassignment->value, 'reference' => $locked->public_id], 'replacement_generation' => $locked->replacement_generation + 1, 'replaces_task_id' => $locked->id, 'activated_at' => $now]);
                $replacement->candidates()->create(['user_id' => $targetUserId, 'source_type' => CandidateSource::Reassignment, 'source_reference' => $locked->public_id, 'user_snapshot_json' => ['id' => $target->id, 'display_name' => $target->displayName], 'is_effective' => true, 'created_at' => $now]);
                $locked->candidates()->update(['is_effective' => false]);
                $locked->update(['status' => TaskStatus::Reassigned, 'replaced_by_task_id' => $replacement->id, 'closed_at' => $now, 'lock_version' => $locked->lock_version + 1]);
                $request->update(['lock_version' => $request->lock_version + 1]);
                $run->update(['lock_version' => $run->lock_version + 1]);
                $this->audit->append('request_task', $locked->public_id, 'request.task.reassigned.v1', $actorId, $correlationId, ['replacement_public_id' => $replacement->public_id, 'target_user_id' => $targetUserId, 'reason' => $reason], $keyHash, $request->id);
                $this->outbox->append('request.task.reassigned.v1', 'request_task', $locked->public_id, $correlationId, ['replacement_public_id' => $replacement->public_id, 'target_user_id' => $targetUserId]);

                return ['task_public_id' => $replacement->public_id];
            });
        }, 3);

        return RequestTask::query()->where('public_id', $response['task_public_id'])->firstOrFail();
    }
}
