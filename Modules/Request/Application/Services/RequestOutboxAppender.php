<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Models\RequestOutboxMessage;

final class RequestOutboxAppender
{
    public function append(string $eventKey, string $aggregateType, string $aggregatePublicId, string $correlationId, array $payload = []): RequestOutboxMessage
    {
        return RequestOutboxMessage::query()->create([
            'event_key' => $eventKey,
            'aggregate_type' => $aggregateType,
            'aggregate_public_id' => $aggregatePublicId,
            'payload_json' => ['version' => 1, 'public_id' => $aggregatePublicId] + $payload,
            'correlation_id' => $correlationId,
            'available_at' => now('UTC'),
        ]);
    }
}
