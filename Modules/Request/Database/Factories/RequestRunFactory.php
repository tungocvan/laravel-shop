<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;

class RequestRunFactory extends Factory
{
    protected $model = RequestRun::class;

    public function definition(): array
    {
        return ['request_instance_id' => InternalRequest::factory(), 'sequence_number' => 1, 'request_type_version_id' => fn (array $attributes): int => InternalRequest::query()->findOrFail($attributes['request_instance_id'])->request_type_version_id, 'request_payload_revision_id' => fn (array $attributes) => RequestPayloadRevision::factory()->create(['request_instance_id' => $attributes['request_instance_id']])->id, 'status' => RunStatus::Active, 'started_by' => 1, 'started_at' => now('UTC'), 'lock_version' => 1];
    }
}
