<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Models\RequestGroup;

class RequestGroupFactory extends Factory
{
    protected $model = RequestGroup::class;

    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->lexify('GROUP_????')), 'name' => fake()->words(2, true), 'created_by' => 1, 'updated_by' => 1];
    }
}
