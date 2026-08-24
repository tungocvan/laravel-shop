<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Request\Models\RequestAuditEvent;

class RequestAuditEventFactory extends Factory
{
    protected $model = RequestAuditEvent::class;

    public function definition(): array
    {
        return ['aggregate_type' => 'request_type', 'aggregate_public_id' => (string) Str::ulid(), 'event_key' => 'request.type.tested.v1', 'actor_user_id' => 1, 'correlation_id' => (string) Str::uuid(), 'occurred_at' => now('UTC')];
    }
}
