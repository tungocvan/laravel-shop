<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

class RequestTypeVersionFactory extends Factory
{
    protected $model = RequestTypeVersion::class;

    public function definition(): array
    {
        return ['request_type_id' => RequestType::factory(), 'version_number' => 1, 'status' => RequestTypeVersionStatus::Draft, 'title' => fake()->sentence(3), 'form_schema_json' => ['schema_version' => 1, 'sections' => []], 'policy_json' => [], 'presentation_json' => [], 'created_by' => 1, 'updated_by' => 1];
    }
}
