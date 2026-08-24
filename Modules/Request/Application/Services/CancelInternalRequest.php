<?php

namespace Modules\Request\Application\Services;

use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;

final class CancelInternalRequest
{
    public function __construct(private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, int $actorId, int $expectedVersion, string $idempotencyKey): InternalRequest
    {
        $response = $this->idempotency->execute($actorId, 'request.draft.cancel', $request->public_id, $idempotencyKey, ['expected_version' => $expectedVersion], function (string $correlationId, string $keyHash) use ($request, $actorId, $expectedVersion): array {
            $locked = InternalRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->requester_id !== $actorId || $locked->status !== RequestStatus::Draft) {
                throw ValidationException::withMessages(['request' => ['draft_not_cancellable']]);
            }
            if ($locked->lock_version !== $expectedVersion) {
                throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
            }
            $locked->update(['status' => RequestStatus::Cancelled, 'cancelled_by' => $actorId, 'cancelled_at' => now('UTC'), 'lock_version' => $locked->lock_version + 1]);
            $this->audit->append('request_instance', $locked->public_id, 'request.cancelled.v1', $actorId, $correlationId, [], $keyHash);
            $this->outbox->append('request.cancelled.v1', 'request_instance', $locked->public_id, $correlationId);

            return ['request_public_id' => $locked->public_id];
        });

        return InternalRequest::query()->where('public_id', $response['request_public_id'])->firstOrFail();
    }
}
