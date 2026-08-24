<?php

namespace Modules\Request\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Request\Domain\Enums\NotificationDeliveryStatus;
use Modules\Request\Models\RequestNotificationDelivery;

class RequestNotificationDeliveryFactory extends Factory
{
    protected $model = RequestNotificationDelivery::class;

    public function definition(): array
    {
        return ['logical_key' => fake()->uuid(), 'channel' => 'database', 'recipient_id' => 1, 'template_key' => 'request.test', 'template_version' => 1, 'status' => NotificationDeliveryStatus::Pending];
    }
}
