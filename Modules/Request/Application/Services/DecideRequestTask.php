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

    public function approve(RequestTask $task, int $actorId, int $requestVersion, int $taskVersion, string $key): RequestDecision
    {
        return $this->handle($task, DecisionType::Approve, null, $actorId, $requestVersion, $taskVersion, $key);
    }

    public function handle(RequestTask $task, DecisionType $type, ?string $reason, int $actorId, int $requestVersion, int $taskVersion, string $key): RequestDecision
    {
        $reason = trim((string) $reason);
        if ($type !== DecisionType::Approve && $reason === '') {
            throw ValidationException::withMessages(['reason' => ['reason_required']]);
        }

        $result = DB::transaction(function () use ($task, $type, $reason, $actorId, $requestVersion, $taskVersion, $key): array {
            $meta = RequestTask::query()->with('run:id,request_instance_id')->findOrFail($task->id);
            $request = InternalRequest::query()->lockForUpdate()->findOrFail($meta->run->request_instance_id);
            $run = RequestRun::query()->lockForUpdate()->findOrFail($meta->request_run_id);
            $tasks = RequestTask::query()->where('request_run_id', $run->id)->where('stage_position', $meta->stage_position)->orderBy('id')->lockForUpdate()->get();
            $target = $tasks->firstWhere('id', $task->id) ?? throw ValidationException::withMessages(['task' => ['task_not_actionable']]);
            $target->candidates()->where('user_id', $actorId)->where('is_effective', true)->lockForUpdate()->first();

            return $this->idempotency->execute($actorId, 'request.task.'.$type->value, $target->public_id, $key, ['decision' => $type->value, 'reason' => $reason, 'request_version' => $requestVersion, 'task_version' => $taskVersion], function (string $correlationId, string $keyHash) use ($request, $run, $tasks, $target, $type, $reason, $actorId, $requestVersion, $taskVersion): array {
                if ($request->status !== RequestStatus::Pending || $run->status !== RunStatus::Active || $request->current_run_id !== $run->id || $target->status !== TaskStatus::Active || $target->suspended_at !== null || $target->stage_position !== $run->current_stage_position || $target->assignee_user_id !== $actorId || $request->requester_id === $actorId || ! $target->candidates()->where('user_id', $actorId)->where('is_effective', true)->exists()) {
                    throw ValidationException::withMessages(['task' => [$target->suspended_at !== null ? 'task_suspended' : 'task_not_actionable']]);
                }
                if ($request->lock_version !== $requestVersion || $target->lock_version !== $taskVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $now = now('UTC');
                $decision = RequestDecision::query()->create(['request_task_id' => $target->id, 'request_run_id' => $run->id, 'request_instance_id' => $request->id, 'decision' => $type, 'actor_user_id' => $actorId, 'effective_actor_user_id' => $actorId, 'reason' => $reason ?: null, 'context_snapshot_json' => ['stage_position' => $target->stage_position], 'idempotency_key_hash' => $keyHash, 'correlation_id' => $correlationId, 'decided_at' => $now, 'created_at' => $now]);
                $target->update(['status' => match ($type) {
                    DecisionType::Approve => TaskStatus::Approved, DecisionType::Reject => TaskStatus::Rejected, DecisionType::Return => TaskStatus::Returned
                }, 'decided_at' => $now, 'closed_at' => $now, 'lock_version' => $target->lock_version + 1]);
                $peers = $tasks->where('id', '!=', $target->id)->where('status', TaskStatus::Active);
                $complete = $type === DecisionType::Approve && ($target->stage_mode === StageMode::ParallelAny || $peers->isEmpty());
                $terminal = $type === DecisionType::Return ? RequestStatus::Returned : ($type === DecisionType::Reject && ($target->stage_mode !== StageMode::ParallelAny || $peers->isEmpty()) ? RequestStatus::Rejected : null);
                if ($terminal) {
                    $peers->each->update(['status' => TaskStatus::Cancelled, 'closed_at' => $now]);
                    $run->update(['status' => $terminal === RequestStatus::Returned ? RunStatus::Returned : RunStatus::Rejected, 'current_stage_position' => null, 'completed_at' => $now, 'terminal_reason' => $reason, 'lock_version' => $run->lock_version + 1]);
                    $request->forceFill(['status' => $terminal, $terminal === RequestStatus::Returned ? 'returned_at' : 'rejected_at' => $now]);
                    $event = $terminal === RequestStatus::Returned ? 'request.returned.v1' : 'request.rejected.v1';
                    $this->audit->append('request_instance', $request->public_id, $event, $actorId, $correlationId, ['run_public_id' => $run->public_id], $keyHash, $request->id);
                    $this->outbox->append($event, 'request_instance', $request->public_id, $correlationId, ['run_public_id' => $run->public_id]);
                } elseif ($complete) {
                    if ($target->stage_mode === StageMode::ParallelAny) {
                        $peers->each->update(['status' => TaskStatus::Skipped, 'closed_at' => $now]);
                    }
                    $this->advance($request, $run, $target->stage_position + 1, $actorId, $correlationId, $keyHash, $now);
                }
                $request->lock_version++;
                $request->save();
                $this->audit->append('request_task', $target->public_id, 'request.task.decided.v1', $actorId, $correlationId, ['decision' => $type->value], $keyHash, $request->id);
                $this->outbox->append('request.task.decided.v1', 'request_task', $target->public_id, $correlationId, ['decision' => $type->value]);

                return ['decision_public_id' => $decision->public_id];
            });
        }, 3);

        return RequestDecision::query()->where('public_id', $result['decision_public_id'])->firstOrFail();
    }

    private function advance(InternalRequest $request, RequestRun $run, int $position, int $actorId, string $correlationId, string $keyHash, mixed $now): void
    {
        if (! RequestStageDefinition::query()->where('request_type_version_id', $request->request_type_version_id)->where('position', $position)->exists()) {
            $run->update(['status' => RunStatus::Approved, 'current_stage_position' => null, 'completed_at' => $now, 'lock_version' => $run->lock_version + 1]);
            $request->forceFill(['status' => RequestStatus::Approved, 'approved_at' => $now]);
            $this->audit->append('request_instance', $request->public_id, 'request.approved.v1', $actorId, $correlationId, ['run_public_id' => $run->public_id], $keyHash, $request->id);
            $this->outbox->append('request.approved.v1', 'request_instance', $request->public_id, $correlationId, ['run_public_id' => $run->public_id]);

            return;
        }
        try {
            $this->activator->activate($request, $run, $position, (array) $run->payloadRevision()->firstOrFail()->payload_json, $actorId, $correlationId, $keyHash);
            $run->update(['lock_version' => $run->lock_version + 1]);
        } catch (ValidationException $exception) {
            $code = array_values($exception->errors())[0][0] ?? 'actor_resolution_failed';
            $run->update(['status' => RunStatus::FailedActivation, 'current_stage_position' => $position, 'activation_error_code' => $code, 'activation_failed_at' => $now, 'activation_retry_count' => $run->activation_retry_count + 1, 'last_activation_correlation_id' => $correlationId, 'lock_version' => $run->lock_version + 1]);
            $this->audit->append('request_instance', $request->public_id, 'request.stage.activation_failed.v1', $actorId, $correlationId, ['stage_position' => $position, 'error_code' => $code], $keyHash, $request->id);
            $this->outbox->append('request.stage.activation_failed.v1', 'request_instance', $request->public_id, $correlationId, ['run_public_id' => $run->public_id, 'stage_position' => $position, 'error_code' => $code]);
        }
    }
}
