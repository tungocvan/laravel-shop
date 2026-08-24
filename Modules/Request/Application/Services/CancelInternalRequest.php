<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestTask;

final class CancelInternalRequest
{
    public function __construct(private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, int $actorId, int $expectedVersion, string $idempotencyKey, ?string $reason = null, bool $cancelAny = false): InternalRequest
    {
        $reason = trim((string) $reason);
        if ($cancelAny && $reason === '') {
            throw ValidationException::withMessages(['reason' => ['reason_required']]);
        }

        $response = DB::transaction(function () use ($request, $actorId, $expectedVersion, $idempotencyKey, $reason, $cancelAny): array {
            $locked = InternalRequest::query()->lockForUpdate()->findOrFail($request->id);

            return $this->idempotency->execute($actorId, 'request.cancel', $locked->public_id, $idempotencyKey, ['expected_version' => $expectedVersion, 'reason' => $reason, 'cancel_any' => $cancelAny], function (string $correlationId, string $keyHash) use ($locked, $actorId, $expectedVersion, $reason, $cancelAny): array {
                $owned = $locked->requester_id === $actorId && in_array($locked->status, [RequestStatus::Draft, RequestStatus::Returned], true);
                $privileged = $cancelAny && $locked->status === RequestStatus::Pending;
                if (! $owned && ! $privileged) {
                    throw ValidationException::withMessages(['request' => ['request_not_cancellable']]);
                }
                if ($locked->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $now = now('UTC');
                if ($privileged) {
                    $run = RequestRun::query()->lockForUpdate()->findOrFail($locked->current_run_id);
                    RequestTask::query()->where('request_run_id', $run->id)->where('status', TaskStatus::Active)->lockForUpdate()->get()->each->update(['status' => TaskStatus::Cancelled, 'closed_at' => $now]);
                    $run->update(['status' => RunStatus::Cancelled, 'current_stage_position' => null, 'completed_at' => $now, 'terminal_reason' => $reason, 'lock_version' => $run->lock_version + 1]);
                }
                $locked->update(['status' => RequestStatus::Cancelled, 'cancelled_by' => $actorId, 'cancellation_reason' => $reason ?: null, 'cancelled_at' => $now, 'lock_version' => $locked->lock_version + 1]);
                $this->audit->append('request_instance', $locked->public_id, 'request.cancelled.v1', $actorId, $correlationId, ['cancel_any' => $privileged], $keyHash, $locked->id);
                $this->outbox->append('request.cancelled.v1', 'request_instance', $locked->public_id, $correlationId);

                return ['request_public_id' => $locked->public_id];
            });
        }, 3);

        return InternalRequest::query()->where('public_id', $response['request_public_id'])->firstOrFail();
    }
}
