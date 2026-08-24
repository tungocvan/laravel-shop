<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Request\Models\RequestIdempotencyKey;

class RequestIdempotencyKeyFactory extends Factory
{
    protected $model = RequestIdempotencyKey::class;

    public function definition(): array
    {
        return ['actor_id' => 1, 'command_key' => 'request.type.publish', 'aggregate_public_id' => (string) Str::ulid(), 'key_hash' => hash('sha256', fake()->uuid()), 'request_fingerprint_hash' => hash('sha256', fake()->uuid()), 'status' => 'processing', 'correlation_id' => (string) Str::uuid(), 'expires_at' => now('UTC')->addHour()];
    }
}
