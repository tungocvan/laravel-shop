<?php

namespace Modules\System\Services;

use App\Modules\ModuleGraphValidator;
use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleStateRepository;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LogicException;
use Modules\System\Exceptions\SystemModuleLifecycleException;
use Throwable;

class SystemModuleControlService
{
    private const LOCK_SECONDS = 180;

    private const LOCK_WAIT_SECONDS = 2;

    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleGraphValidator $validator,
        private readonly ModuleLifecycleManager $lifecycle,
        private readonly ModulePermissionManager $permissions,
        private readonly ModuleStateRepository $states,
    ) {}

    public function toggle(string $moduleName, ?int $actorId = null): array
    {
        return $this->withModuleLock($moduleName, function () use ($moduleName, $actorId): array {
            $modules = $this->registry->fresh();
            $module = $this->module($modules, $moduleName);
            $newEnabled = ! $module['enabled'];
            $queueNames = $this->moduleQueueNames($module);

            $context = [
                'actor_id' => $actorId,
                'operation' => 'module.toggle',
                'module' => $moduleName,
                'target_enabled' => $newEnabled,
            ];

            Log::notice('System module control started.', $context + ['stage' => 'preflight']);

            try {
                $updatedModules = $this->validator->withState($modules, $moduleName, $newEnabled);
            } catch (LogicException $e) {
                Log::notice('System module control rejected.', $context + [
                    'stage' => 'dependency',
                    'exception' => $e::class,
                ]);

                throw $e;
            }

            $migration = ['migrated' => false];
            $permissionCount = 0;

            if ($newEnabled) {
                Log::notice('System module control stage.', $context + ['stage' => 'migration']);

                try {
                    $migration = $this->lifecycle->migrateIfNeeded($module);
                } catch (Throwable $e) {
                    Log::error('System module migration failed.', $context + ['exception' => $e::class]);

                    throw new SystemModuleLifecycleException(
                        stage: 'migration',
                        safeMessage: 'Không thể hoàn tất migration của Module.',
                        guidance: 'Kiểm tra trạng thái migration/schema của Module. Nếu migration ledger không đồng bộ, hãy chạy chẩn đoán và phục hồi ledger trước khi bật lại.',
                        previous: $e,
                    );
                }

                Log::notice('System module control stage.', $context + ['stage' => 'permission_sync']);

                try {
                    $permissionCount = $this->permissions->sync($module);
                } catch (Throwable $e) {
                    Log::error('System module permission sync failed.', $context + ['exception' => $e::class]);

                    throw new SystemModuleLifecycleException(
                        stage: 'permission_sync',
                        safeMessage: 'Migration đã hoàn tất nhưng đồng bộ permission thất bại; Module chưa được bật.',
                        guidance: 'Kiểm tra config/module.php, permission guard và bảng permission/role. Sau khi sửa, bật lại Module để đồng bộ lại quyền.',
                        previous: $e,
                    );
                }
            }

            Log::notice('System module control stage.', $context + ['stage' => 'runtime_state']);

            try {
                $this->states->set($moduleName, $newEnabled);
                $this->registry->publish($updatedModules);
            } catch (Throwable $e) {
                Log::error('System module runtime state update failed.', $context + ['exception' => $e::class]);

                throw new SystemModuleLifecycleException(
                    stage: 'runtime_state',
                    safeMessage: 'Không thể ghi trạng thái runtime của Module.',
                    guidance: 'Kiểm tra quyền ghi storage/app/system/module-state.json và cache/runtime registry rồi thử lại.',
                    previous: $e,
                );
            }

            if (! $newEnabled) {
                $this->permissions->forgetCache();
            }

            $queueRestartRequested = false;
            if ($queueNames !== []) {
                Log::notice('System module control stage.', $context + [
                    'stage' => 'queue_restart',
                    'queues' => $queueNames,
                ]);

                try {
                    Artisan::call('queue:restart');
                    $queueRestartRequested = true;
                } catch (Throwable $e) {
                    Log::warning('System module queue restart signal failed.', $context + [
                        'exception' => $e::class,
                        'queues' => $queueNames,
                    ]);
                }
            }

            Log::notice('System module control completed.', $context + [
                'stage' => 'completed',
                'migrated' => (bool) ($migration['migrated'] ?? false),
                'permission_count' => $permissionCount,
                'queues' => $queueNames,
                'queue_restart_requested' => $queueRestartRequested,
            ]);

            return [
                'module' => $moduleName,
                'enabled' => $newEnabled,
                'migrated' => (bool) ($migration['migrated'] ?? false),
                'permission_count' => $permissionCount,
                'queues' => $queueNames,
                'queue_restart_requested' => $queueRestartRequested,
            ];
        });
    }

    private function module(Collection $modules, string $moduleName): array
    {
        $module = $modules->firstWhere('name', $moduleName);

        if (! is_array($module)) {
            throw new LogicException('Module không tồn tại trong catalog.');
        }

        return $module;
    }

    private function moduleQueueNames(array $module): array
    {
        $path = rtrim((string) ($module['path'] ?? ''), '/\\');
        $defaultQueue = (string) config('queue.connections.'.config('queue.default').'.queue', 'default');

        foreach ([$path.'/config/module.php', $path.'/Config/module.php'] as $manifest) {
            if (! is_file($manifest)) {
                continue;
            }

            $config = require $manifest;
            if (! is_array($config)) {
                return [];
            }

            return collect((array) ($config['queues'] ?? []))
                ->filter(fn (mixed $definition): bool => is_array($definition) && is_string($definition['name'] ?? null))
                ->map(fn (array $definition): string => trim((string) $definition['name']))
                ->filter(fn (string $name): bool => $name !== '' && $name !== $defaultQueue)
                ->unique()
                ->values()
                ->all();
        }

        return [];
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
