<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestComment;

class RequestCommentFactory extends Factory
{
    protected $model = RequestComment::class;

    public function definition(): array
    {
        return ['request_instance_id' => InternalRequest::factory(), 'author_id' => 1, 'body' => fake()->sentence(), 'body_format' => 'plain', 'created_at' => now('UTC')];
    }
}
