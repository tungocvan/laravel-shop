<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Request\Models\RequestOutboxMessage;

class RequestOutboxMessageFactory extends Factory
{
    protected $model = RequestOutboxMessage::class;

    public function definition(): array
    {
        return ['event_key' => 'request.type.tested.v1', 'aggregate_type' => 'request_type', 'aggregate_public_id' => (string) Str::ulid(), 'payload_json' => ['version' => 1], 'correlation_id' => (string) Str::uuid(), 'available_at' => now('UTC')];
    }
}
