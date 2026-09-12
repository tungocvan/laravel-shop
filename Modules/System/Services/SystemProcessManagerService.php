<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SystemProcessManagerService
{
    private const ALLOWED_ACTIONS = ['start', 'stop', 'restart'];

    /**
     * Only these application runtimes are manageable from the browser.
     * PM2 may supervise other host processes, but System must never expose them.
     */
    private const MANAGED_PROCESS_NAMES = [
        'Queue-laravel-shop',
        'Request-Queue-laravel-shop',
        'Scheduler-laravel-shop',
        'Socketio-laravel-shop',
    ];

    public function status(): array
    {
        try {
            $result = $this->run(['jlist'], 5.0);

            if (! $result->isSuccessful()) {
                return $this->unavailableStatus();
            }

            $payload = json_decode($result->getOutput(), true);

            if (! is_array($payload)) {
                return $this->unavailableStatus();
            }

            $items = collect($payload)
                ->filter(fn (mixed $process): bool => is_array($process)
                    && in_array((string) ($process['name'] ?? ''), self::MANAGED_PROCESS_NAMES, true))
                ->map(fn (array $process): array => $this->normalizeProcess($process))
                ->sortBy(fn (array $process): int => array_search($process['name'], self::MANAGED_PROCESS_NAMES, true))
                ->values()
                ->all();

            return [
                'available' => true,
                'online' => collect($items)->where('status', 'online')->count(),
                'total' => count($items),
                'items' => $items,
            ];
        } catch (Throwable $e) {
            Log::warning('System PM2 status lookup failed.', [
                'exception' => $e::class,
            ]);

            return $this->unavailableStatus();
        }
    }

    public function act(string $name, string $action, ?int $actorId = null): void
    {
        if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
            throw new RuntimeException('Unsupported process action.');
        }

        if (! in_array($name, self::MANAGED_PROCESS_NAMES, true)) {
            throw new RuntimeException('Process is not managed by System.');
        }

        $status = $this->status();
        $knownNames = collect($status['items'] ?? [])->pluck('name')->all();

        if (! ($status['available'] ?? false) || ! in_array($name, $knownNames, true)) {
            throw new RuntimeException('Managed PM2 process is unavailable.');
        }

        Log::notice('System PM2 process action started.', [
            'actor_id' => $actorId,
            'process' => $name,
            'action' => $action,
        ]);

        $result = $this->run([$action, $name], 15.0);

        if (! $result->isSuccessful()) {
            Log::warning('System PM2 process action failed.', [
                'actor_id' => $actorId,
                'process' => $name,
                'action' => $action,
            ]);

            throw new RuntimeException('PM2 process action failed.');
        }

        Log::notice('System PM2 process action completed.', [
            'actor_id' => $actorId,
            'process' => $name,
            'action' => $action,
        ]);
    }

    private function run(array $arguments, float $timeout): Process
    {
        $process = new Process(
            array_merge([$this->binary()], $arguments),
            base_path(),
            $this->environment(),
            null,
            $timeout,
        );
        $process->run();

        return $process;
    }

    private function binary(): string
    {
        $binary = trim((string) env('SYSTEM_PM2_BINARY', 'pm2'));

        return $binary !== '' ? $binary : 'pm2';
    }

    private function environment(): ?array
    {
        $home = trim((string) env('SYSTEM_PM2_HOME', ''));

        return $home === '' ? null : ['PM2_HOME' => $home];
    }

    private function normalizeProcess(array $process): array
    {
        $env = is_array($process['pm2_env'] ?? null) ? $process['pm2_env'] : [];
        $monit = is_array($process['monit'] ?? null) ? $process['monit'] : [];
        $name = (string) ($process['name'] ?? '');
        $startedAt = (int) ($env['pm_uptime'] ?? 0);

        return [
            'name' => $name,
            'role' => $this->role($name),
            'status' => (string) ($env['status'] ?? 'unknown'),
            'cpu' => (float) ($monit['cpu'] ?? 0),
            'memory_bytes' => (int) ($monit['memory'] ?? 0),
            'restarts' => (int) ($env['restart_time'] ?? 0),
            'started_at' => $startedAt > 0 ? $startedAt : null,
            'pm_id' => isset($process['pm_id']) ? (int) $process['pm_id'] : null,
        ];
    }

    private function role(string $name): string
    {
        return match ($name) {
            'Queue-laravel-shop' => 'Queue mặc định',
            'Request-Queue-laravel-shop' => 'Request Queue',
            'Scheduler-laravel-shop' => 'Scheduler',
            'Socketio-laravel-shop' => 'Realtime Socket.IO',
            default => 'Runtime',
        };
    }

    private function unavailableStatus(): array
    {
        return [
            'available' => false,
            'online' => 0,
            'total' => 0,
            'items' => [],
        ];
    }
}
