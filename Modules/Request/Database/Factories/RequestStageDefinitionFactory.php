<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Models\RequestStageDefinition;
use Modules\Request\Models\RequestTypeVersion;

class RequestStageDefinitionFactory extends Factory
{
    protected $model = RequestStageDefinition::class;

    public function definition(): array
    {
        return ['request_type_version_id' => RequestTypeVersion::factory(), 'stage_key' => 'approval', 'name' => 'Approval', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [1]]];
    }
}
