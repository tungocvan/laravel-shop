<?php

namespace Modules\System\Services;

use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueRegistryService
{
    public function __construct(private readonly ModuleRegistry $modules) {}

    public function queues(): array
    {
        $queues = [];
        $disabledOwnedQueues = [];
        $runtimeModules = $this->modules->current()->keyBy('name');

        $defaultQueue = (string) config('queue.connections.'.config('queue.default').'.queue', 'default');
        if ($defaultQueue !== '') {
            $queues[$defaultQueue] = $this->defaults($defaultQueue, 'System', 'Queue mặc định của ứng dụng.');
        }

        foreach (glob(base_path('Modules/*/config/module.php')) ?: [] as $configFile) {
            $config = require $configFile;

            if (! is_array($config)) {
                continue;
            }

            $module = (string) ($config['name'] ?? basename(dirname(dirname($configFile))));
            $enabled = (bool) data_get($runtimeModules->get($module), 'enabled', $config['enabled'] ?? true);

            foreach (($config['queues'] ?? []) as $definition) {
                if (! is_array($definition) || empty($definition['name'])) {
                    continue;
                }

                $name = trim((string) $definition['name']);
                if ($name === '') {
                    continue;
                }

                if (! $enabled) {
                    $disabledOwnedQueues[$name] = true;
                    continue;
                }

                $queues[$name] = array_merge($this->defaults($name, $module), $definition, [
                    'module' => $module,
                    'name' => $name,
                    'source' => 'module',
                ]);
            }
        }

        foreach ($this->discoveredQueueNames() as $name) {
            if (isset($disabledOwnedQueues[$name])) {
                continue;
            }

            $queues[$name] ??= $this->defaults($name, 'Runtime', 'Queue được phát hiện từ dữ liệu jobs/failed_jobs hiện tại.');
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

        $lastProbeAt = Cache::get($this->probeCacheKey($queue));

        return [
            'pending' => $pending,
            'reserved' => $reserved,
            'failed' => $failed,
            'last_probe_at' => $lastProbeAt,
            'state' => $failed > 0
                ? 'attention'
                : ($reserved > 0 ? 'processing' : ($pending > 0 ? 'waiting' : 'idle')),
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
        return 'system.queue_probe.'.$queue;
    }

    private function defaults(string $name, string $module, ?string $description = null): array
    {
        return [
            'module' => $module,
            'name' => $name,
            'workers' => 1,
            'timeout' => 180,
            'tries' => 3,
            'sleep' => 2,
            'max_jobs' => 100,
            'max_time' => 3600,
            'description' => $description,
            'source' => 'system',
        ];
    }

    private function discoveredQueueNames(): array
    {
        $names = collect();

        if (Schema::hasTable('jobs')) {
            $names = $names->merge(DB::table('jobs')->distinct()->pluck('queue'));
        }

        if (Schema::hasTable('failed_jobs')) {
            $names = $names->merge(DB::table('failed_jobs')->distinct()->pluck('queue'));
        }

        return $names
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->map(fn (string $name): string => trim($name))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
