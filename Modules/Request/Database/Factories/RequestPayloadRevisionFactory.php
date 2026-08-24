<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestPayloadRevision;

class RequestPayloadRevisionFactory extends Factory
{
    protected $model = RequestPayloadRevision::class;

    public function definition(): array
    {
        return ['request_instance_id' => InternalRequest::factory(), 'revision_number' => 1, 'request_type_version_id' => fn (array $attributes): int => InternalRequest::query()->findOrFail($attributes['request_instance_id'])->request_type_version_id, 'payload_json' => [], 'display_snapshot_json' => [], 'payload_checksum' => hash('sha256', '{}'), 'schema_version' => 1, 'source' => PayloadSource::ServerDraft, 'created_by' => 1];
    }
}
