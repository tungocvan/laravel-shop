<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Approval\ApprovalStageActivator;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestDecision;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestStageDefinition;
use Modules\Request\Models\RequestTask;

final class DecideRequestTask
{
    public function __construct(private readonly ApprovalStageActivator $activator, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function approve(RequestTask $task, int $actorId, int $expectedRequestVersion, int $expectedTaskVersion, string $idempotencyKey): RequestDecision
    {
        $response = DB::transaction(function () use ($task, $actorId, $expectedRequestVersion, $expectedTaskVersion, $idempotencyKey): array {
            $metadata = RequestTask::query()->with('run:id,request_instance_id')->whereKey($task->id)->firstOrFail();
            $request = InternalRequest::query()->lockForUpdate()->findOrFail($metadata->run->request_instance_id);
            $run = RequestRun::query()->lockForUpdate()->findOrFail($metadata->request_run_id);
            $stageTasks = RequestTask::query()->where('request_run_id', $run->id)->where('stage_position', $metadata->stage_position)->orderBy('id')->lockForUpdate()->get();
            $lockedTask = $stageTasks->firstWhere('id', $task->id) ?? throw ValidationException::withMessages(['task' => ['task_not_actionable']]);
            $lockedTask->candidates()->where('user_id', $actorId)->where('is_effective', true)->lockForUpdate()->first();

            return $this->idempotency->execute($actorId, 'request.task.approve', $lockedTask->public_id, $idempotencyKey, ['expected_request_version' => $expectedRequestVersion, 'expected_task_version' => $expectedTaskVersion], function (string $correlationId, string $keyHash) use ($request, $run, $lockedTask, $actorId, $expectedRequestVersion, $expectedTaskVersion): array {
                if ($request->status !== RequestStatus::Pending || $run->status !== RunStatus::Active || $request->current_run_id !== $run->id || $lockedTask->status !== TaskStatus::Active || $lockedTask->stage_mode !== StageMode::Single || $lockedTask->stage_position !== $run->current_stage_position || $lockedTask->assignee_user_id !== $actorId || $request->requester_id === $actorId || ! $lockedTask->candidates()->where('user_id', $actorId)->where('is_effective', true)->exists()) {
                    throw ValidationException::withMessages(['task' => ['task_not_actionable']]);
                }
                if ($request->lock_version !== $expectedRequestVersion || $lockedTask->lock_version !== $expectedTaskVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }

                $now = now('UTC');
                $decision = RequestDecision::query()->create([
                    'request_task_id' => $lockedTask->id,
                    'request_run_id' => $run->id,
                    'request_instance_id' => $request->id,
                    'decision' => DecisionType::Approve,
                    'actor_user_id' => $actorId,
                    'effective_actor_user_id' => $actorId,
                    'context_snapshot_json' => ['stage_position' => $lockedTask->stage_position, 'stage_key' => $lockedTask->stage_key_snapshot],
                    'idempotency_key_hash' => $keyHash,
                    'correlation_id' => $correlationId,
                    'decided_at' => $now,
                    'created_at' => $now,
                ]);
                $lockedTask->update(['status' => TaskStatus::Approved, 'decided_at' => $now, 'closed_at' => $now, 'lock_version' => $lockedTask->lock_version + 1]);
                $nextPosition = $lockedTask->stage_position + 1;
                $hasNext = RequestStageDefinition::query()->where('request_type_version_id', $request->request_type_version_id)->where('position', $nextPosition)->exists();
                if ($hasNext) {
                    $payload = (array) $run->payloadRevision()->firstOrFail()->payload_json;
                    $this->activator->activate($request, $run, $nextPosition, $payload, $actorId, $correlationId, $keyHash);
                    $run->update(['lock_version' => $run->lock_version + 1]);
                } else {
                    $run->update(['status' => RunStatus::Approved, 'current_stage_position' => null, 'completed_at' => $now, 'lock_version' => $run->lock_version + 1]);
                    $request->approved_at = $now;
                    $request->status = RequestStatus::Approved;
                }
                $request->lock_version++;
                $request->save();
                $this->audit->append('request_task', $lockedTask->public_id, 'request.task.decided.v1', $actorId, $correlationId, ['decision' => DecisionType::Approve->value, 'request_public_id' => $request->public_id], $keyHash, $request->id);
                $this->outbox->append('request.task.decided.v1', 'request_task', $lockedTask->public_id, $correlationId, ['decision' => DecisionType::Approve->value, 'request_public_id' => $request->public_id]);
                if (! $hasNext) {
                    $this->audit->append('request_instance', $request->public_id, 'request.instance.approved.v1', $actorId, $correlationId, ['run_public_id' => $run->public_id], $keyHash, $request->id);
                    $this->outbox->append('request.instance.approved.v1', 'request_instance', $request->public_id, $correlationId, ['run_public_id' => $run->public_id]);
                }

                return ['decision_public_id' => $decision->public_id, 'request_public_id' => $request->public_id, 'status' => $request->status->value];
            });
        }, 3);

        return RequestDecision::query()->where('public_id', $response['decision_public_id'])->firstOrFail();
    }
}
