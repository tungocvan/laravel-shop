<?php

namespace Modules\System\Services;

use App\Modules\ModuleGraphValidator;
use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleStateRepository;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LogicException;
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

            $context = [
                'actor_id' => $actorId,
                'operation' => 'module.toggle',
                'module' => $moduleName,
                'target_enabled' => $newEnabled,
            ];

            Log::notice('System module control started.', $context + ['stage' => 'preflight']);

            try {
                $updatedModules = $this->validator->withState($modules, $moduleName, $newEnabled);
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
                $this->registry->publish($updatedModules);

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

    private function module(Collection $modules, string $moduleName): array
    {
        $module = $modules->firstWhere('name', $moduleName);

        if (! is_array($module)) {
            throw new LogicException('Module không tồn tại trong catalog.');
        }

        return $module;
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
