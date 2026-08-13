<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueRegistryService
{
    public function queues(): array
    {
        $queues = [];

        foreach (glob(base_path('Modules/*/config/module.php')) ?: [] as $configFile) {
            $config = require $configFile;

            if (! is_array($config) || ! ($config['enabled'] ?? true)) {
                continue;
            }

            $module = (string) ($config['name'] ?? basename(dirname(dirname($configFile))));

            foreach (($config['queues'] ?? []) as $definition) {
                if (! is_array($definition) || empty($definition['name'])) {
                    continue;
                }

                $name = (string) $definition['name'];
                $queues[$name] = array_merge([
                    'module' => $module,
                    'name' => $name,
                    'workers' => 1,
                    'timeout' => 180,
                    'tries' => 3,
                    'sleep' => 2,
                    'max_jobs' => 100,
                    'max_time' => 3600,
                    'description' => null,
                ], $definition, [
                    'module' => $module,
                    'name' => $name,
                ]);
            }
        }

        ksort($queues);

        return array_values($queues);
    }

    public function status(string $queue): array
    {
        $pending = 0;
        $reserved = 0;
        $failed = 0;

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')
                ->where('queue', $queue)
                ->whereNull('reserved_at')
                ->count();

            $reserved = DB::table('jobs')
                ->where('queue', $queue)
                ->whereNotNull('reserved_at')
                ->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();
        }

        return [
            'pending' => $pending,
            'reserved' => $reserved,
            'failed' => $failed,
            'last_probe_at' => Cache::get($this->probeCacheKey($queue)),
        ];
    }

    public function command(array $queue): string
    {
        return sprintf(
            'php artisan queue:work --queue=%s --sleep=%d --tries=%d --timeout=%d --max-jobs=%d --max-time=%d',
            $queue['name'],
            (int) $queue['sleep'],
            (int) $queue['tries'],
            (int) $queue['timeout'],
            (int) $queue['max_jobs'],
            (int) $queue['max_time'],
        );
    }

    public function probeCacheKey(string $queue): string
    {
        return 'system.queue_probe.' . $queue;
    }
}
