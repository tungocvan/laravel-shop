<?php

namespace Modules\Request\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Request\Application\Services\RequestOutboxDispatcher;

class DispatchRequestOutboxBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly ?int $limit = null)
    {
        $this->onQueue((string) config('request.notifications.outbox_queue', 'request-outbox'));
    }

    public function handle(RequestOutboxDispatcher $dispatcher): void
    {
        $dispatcher->dispatchDue($this->limit);
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }
}
