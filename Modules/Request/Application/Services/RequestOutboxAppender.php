<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Models\RequestOutboxMessage;

final class RequestOutboxAppender
{
    public function append(string $eventKey, string $aggregateType, string $aggregatePublicId, string $correlationId, array $payload = []): RequestOutboxMessage
    {
        $occurredAt = now('UTC');

        return RequestOutboxMessage::query()->create([
            'event_key' => $eventKey,
            'aggregate_type' => $aggregateType,
            'aggregate_public_id' => $aggregatePublicId,
            'payload_json' => ['version' => 1, 'event_key' => $eventKey, 'aggregate_type' => $aggregateType, 'public_id' => $aggregatePublicId, 'occurred_at' => $occurredAt->toIso8601String(), 'correlation_id' => $correlationId] + $payload,
            'correlation_id' => $correlationId,
            'available_at' => $occurredAt,
        ]);
    }
}
