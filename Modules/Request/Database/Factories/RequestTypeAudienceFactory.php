<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Models\RequestTypeAudience;
use Modules\Request\Models\RequestTypeVersion;

class RequestTypeAudienceFactory extends Factory
{
    protected $model = RequestTypeAudience::class;

    public function definition(): array
    {
        return ['request_type_version_id' => RequestTypeVersion::factory(), 'actor_type' => 'user', 'actor_id' => 1, 'capability' => 'create'];
    }
}
