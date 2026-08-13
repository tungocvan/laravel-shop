<?php

namespace Modules\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\System\Services\QueueRegistryService;

class QueueProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    public function __construct(public string $queueName)
    {
        $this->onQueue($queueName);
    }

    public function handle(QueueRegistryService $registry): void
    {
        Cache::put(
            $registry->probeCacheKey($this->queueName),
            now()->toIso8601String(),
            now()->addDay(),
        );
    }
}
