<?php

namespace Modules\Request\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Request\Application\Services\RequestNotificationDeliverer;

class DeliverRequestNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly string $deliveryPublicId)
    {
        $this->onQueue((string) config('request.notifications.queue', 'request-notifications'));
    }

    public function handle(RequestNotificationDeliverer $deliverer): void
    {
        $deliverer->deliver($this->deliveryPublicId);
    }

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }
}
