<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Approval\ApprovalStageActivator;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestRun;

final class RetryStageActivation
{
    public function __construct(private readonly ApprovalStageActivator $activator, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit) {}

    public function handle(InternalRequest $request, int $actorId, int $expectedVersion, string $idempotencyKey): RequestRun
    {
        $response = DB::transaction(function () use ($request, $actorId, $expectedVersion, $idempotencyKey): array {
            $locked = InternalRequest::query()->lockForUpdate()->findOrFail($request->id);
            $run = RequestRun::query()->lockForUpdate()->findOrFail($locked->current_run_id);

            return $this->idempotency->execute($actorId, 'request.stage.retry', $locked->public_id, $idempotencyKey, ['expected_version' => $expectedVersion, 'run_public_id' => $run->public_id], function (string $correlationId, string $keyHash) use ($locked, $run, $actorId, $expectedVersion): array {
                if ($locked->status !== RequestStatus::Pending || $run->status !== RunStatus::FailedActivation || ! $run->current_stage_position || $run->tasks()->where('stage_position', $run->current_stage_position)->exists()) {
                    throw ValidationException::withMessages(['request' => ['activation_not_retryable']]);
                }
                if ($locked->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $this->activator->activate($locked, $run, $run->current_stage_position, (array) $run->payloadRevision()->value('payload_json'), $actorId, $correlationId, $keyHash);
                $run->update(['activation_retry_count' => $run->activation_retry_count + 1, 'lock_version' => $run->lock_version + 1]);
                $locked->update(['lock_version' => $locked->lock_version + 1]);
                $this->audit->append('request_instance', $locked->public_id, 'request.stage.activation_retried.v1', $actorId, $correlationId, ['stage_position' => $run->current_stage_position], $keyHash, $locked->id);

                return ['run_public_id' => $run->public_id];
            });
        }, 3);

        return RequestRun::query()->where('public_id', $response['run_public_id'])->firstOrFail();
    }
}
