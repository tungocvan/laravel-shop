<?php

namespace Modules\System\Services;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleStateRepository;
use App\Services\RealtimeManager;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class SystemModuleControlService
{
    private const LOCK_SECONDS = 180;
    private const LOCK_WAIT_SECONDS = 2;

    public function __construct(
        private readonly ModuleLifecycleManager $lifecycle,
        private readonly ModulePermissionManager $permissions,
        private readonly ModuleStateRepository $states,
    ) {
    }

    public function toggle(string $moduleName, ?int $actorId = null): array
    {
        return $this->withModuleLock($moduleName, function () use ($moduleName, $actorId): array {
            $registry = $this->registry();
            $module = $this->module($registry, $moduleName);
            $newEnabled = ! (bool) ($module['enabled'] ?? false);

            $context = [
                'actor_id' => $actorId,
                'operation' => 'module.toggle',
                'module' => $moduleName,
                'target_enabled' => $newEnabled,
            ];

            Log::notice('System module control started.', $context + ['stage' => 'preflight']);

            try {
                $this->assertToggleAllowed($registry, $module, $newEnabled);

                $migration = ['migrated' => false];
                $permissionCount = 0;

                if ($newEnabled) {
                    Log::notice('System module control stage.', $context + ['stage' => 'migration']);
                    $migration = $this->lifecycle->migrateIfNeeded($module);

                    Log::notice('System module control stage.', $context + ['stage' => 'permission_sync']);
                    $permissionCount = $this->permissions->sync($module);
                }

                Log::notice('System module control stage.', $context + ['stage' => 'runtime_state']);
                $this->states->set($moduleName, $newEnabled);
                config(["modules.registry.{$moduleName}.enabled" => $newEnabled]);
                config(["modules.registry.{$moduleName}.source" => 'runtime']);

                if (! $newEnabled) {
                    $this->permissions->forgetCache();
                }

                Log::notice('System module control completed.', $context + [
                    'stage' => 'completed',
                    'migrated' => (bool) ($migration['migrated'] ?? false),
                    'permission_count' => $permissionCount,
                ]);

                return [
                    'module' => $moduleName,
                    'enabled' => $newEnabled,
                    'migrated' => (bool) ($migration['migrated'] ?? false),
                    'permission_count' => $permissionCount,
                ];
            } catch (Throwable $e) {
                Log::error('System module control failed.', $context + [
                    'exception' => $e::class,
                ]);

                throw $e;
            }
        });
    }

    public function archive(string $moduleName, ?int $actorId = null): array
    {
        return $this->withModuleLock($moduleName, function () use ($moduleName, $actorId): array {
            $registry = $this->registry();
            $module = $this->module($registry, $moduleName);
            $context = [
                'actor_id' => $actorId,
                'operation' => 'module.archive',
                'module' => $moduleName,
            ];

            Log::notice('System module archive started.', $context);

            try {
                $destination = $this->lifecycle->archive($module, $registry);

                Log::notice('System module archive stage.', $context + ['stage' => 'runtime_state_cleanup']);
                $this->states->forget($moduleName);

                unset($registry[$moduleName]);
                config(['modules.registry' => $registry]);

                Log::notice('System module archive completed.', $context + [
                    'archive' => basename($destination),
                ]);

                return [
                    'module' => $moduleName,
                    'archive' => basename($destination),
                ];
            } catch (Throwable $e) {
                Log::error('System module archive failed.', $context + [
                    'exception' => $e::class,
                ]);

                throw $e;
            }
        });
    }

    public function toggleRealtime(RealtimeManager $realtime, bool $currentlyEnabled, ?int $actorId = null): bool
    {
        $target = ! $currentlyEnabled;

        Log::notice('System realtime toggle started.', [
            'actor_id' => $actorId,
            'target_enabled' => $target,
        ]);

        try {
            $realtime->setEnabled($target);

            Log::notice('System realtime toggle completed.', [
                'actor_id' => $actorId,
                'target_enabled' => $target,
            ]);

            return $target;
        } catch (Throwable $e) {
            Log::error('System realtime toggle failed.', [
                'actor_id' => $actorId,
                'target_enabled' => $target,
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    private function registry(): array
    {
        $registry = config('modules.registry', []);

        return is_array($registry) ? $registry : [];
    }

    private function module(array $registry, string $moduleName): array
    {
        $module = $registry[$moduleName] ?? null;

        if (! is_array($module)) {
            throw new LogicException('Module không tồn tại trong registry.');
        }

        return $module + [
            'name' => $moduleName,
            'depends' => [],
            'required' => ($module['type'] ?? null) === 'shell',
        ];
    }

    private function assertToggleAllowed(array $registry, array $module, bool $newEnabled): void
    {
        if (! $newEnabled && (bool) ($module['required'] ?? false)) {
            throw new LogicException('Module hệ thống bắt buộc không thể tắt.');
        }

        if ($newEnabled) {
            $disabledDependencies = collect($module['depends'] ?? [])
                ->filter(fn (string $dependency): bool => ! (bool) (($registry[$dependency]['enabled'] ?? false)))
                ->values();

            if ($disabledDependencies->isNotEmpty()) {
                throw new LogicException('Hãy bật các module phụ thuộc trước: '.$disabledDependencies->join(', ').'.');
            }

            return;
        }

        $dependents = collect($registry)
            ->filter(fn (array $candidate): bool => (bool) ($candidate['enabled'] ?? false)
                && in_array($module['name'], $candidate['depends'] ?? [], true))
            ->keys()
            ->values();

        if ($dependents->isNotEmpty()) {
            throw new LogicException('Hãy tắt các module đang phụ thuộc trước: '.$dependents->join(', ').'.');
        }
    }

    private function withModuleLock(string $moduleName, callable $callback): mixed
    {
        $key = 'system:module-control:'.sha1($moduleName);
        $lock = Cache::lock($key, self::LOCK_SECONDS);

        try {
            return $lock->block(self::LOCK_WAIT_SECONDS, $callback);
        } catch (LockTimeoutException) {
            throw new LogicException('Một thao tác khác trên module này đang được xử lý. Vui lòng thử lại sau.');
        }
    }
}
