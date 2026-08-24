<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestTypeVersion;

class InternalRequestFactory extends Factory
{
    protected $model = InternalRequest::class;

    public function definition(): array
    {
        return ['request_number' => 'REQ-'.now('UTC')->format('Y').'-'.$this->faker->unique()->numerify('########'), 'request_type_version_id' => RequestTypeVersion::factory(), 'request_type_id' => fn (array $attributes): int => RequestTypeVersion::query()->findOrFail($attributes['request_type_version_id'])->request_type_id, 'requester_id' => 1, 'status' => RequestStatus::Draft, 'title_snapshot' => $this->faker->sentence(3), 'requester_snapshot_json' => ['id' => 1, 'display_name' => 'Requester'], 'lock_version' => 1];
    }
}
