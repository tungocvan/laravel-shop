<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Models\RequestAuditEvent;

final class RequestAuditAppender
{
    public function append(string $aggregateType, string $aggregatePublicId, string $eventKey, int $actorId, string $correlationId, array $context = [], ?string $idempotencyKeyHash = null, ?int $requestInstanceId = null): RequestAuditEvent
    {
        return RequestAuditEvent::query()->create([
            'request_instance_id' => $requestInstanceId,
            'aggregate_type' => $aggregateType,
            'aggregate_public_id' => $aggregatePublicId,
            'event_key' => $eventKey,
            'actor_user_id' => $actorId,
            'effective_actor_user_id' => $actorId,
            'context_json' => $context,
            'correlation_id' => $correlationId,
            'idempotency_key_hash' => $idempotencyKeyHash,
            'occurred_at' => now('UTC'),
        ]);
    }
}
