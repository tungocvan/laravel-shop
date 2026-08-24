<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

class RequestTypeFactory extends Factory
{
    protected $model = RequestType::class;

    public function definition(): array
    {
        return ['request_group_id' => RequestGroup::factory(), 'code' => strtoupper(fake()->unique()->lexify('TYPE_????')), 'name' => fake()->words(3, true), 'status' => RequestTypeStatus::Draft, 'created_by' => 1, 'updated_by' => 1];
    }
}
