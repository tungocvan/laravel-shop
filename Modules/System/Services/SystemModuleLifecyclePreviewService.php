<?php

namespace Modules\System\Services;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SystemModuleLifecyclePreviewService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleLifecycleManager $lifecycle,
        private readonly ModulePermissionManager $permissions,
    ) {}

    public function preview(string $moduleName): array
    {
        $modules = $this->registry->fresh();
        $module = $modules->firstWhere('name', $moduleName);

        if (! is_array($module)) {
            throw new RuntimeException('Module không tồn tại trong registry.');
        }

        $targetEnabled = ! (bool) ($module['enabled'] ?? false);
        $dependencies = collect((array) ($module['depends'] ?? []))
            ->map(function (string $dependency) use ($modules): array {
                $registered = $modules->firstWhere('name', $dependency);

                return [
                    'name' => $dependency,
                    'enabled' => is_array($registered) && (bool) ($registered['enabled'] ?? false),
                ];
            })
            ->values()
            ->all();

        $usedBy = $modules
            ->filter(fn (array $candidate): bool => (bool) ($candidate['enabled'] ?? false)
                && in_array($moduleName, (array) ($candidate['depends'] ?? []), true))
            ->pluck('name')
            ->values()
            ->all();

        $database = $this->databasePreview($module, $targetEnabled);
        $permission = $this->permissionPreview($moduleName, $targetEnabled);
        $queues = $this->queuePreview($module);
        $blocking = [];

        if (! $targetEnabled && (bool) ($module['required'] ?? false)) {
            $blocking[] = 'Module bắt buộc của hệ thống không thể tắt.';
        }

        if ($targetEnabled) {
            $disabledDependencies = collect($dependencies)->where('enabled', false)->pluck('name')->values()->all();
            if ($disabledDependencies !== []) {
                $blocking[] = 'Cần bật Module phụ thuộc trước: '.implode(', ', $disabledDependencies).'.';
            }

            if (! ($database['can_execute'] ?? false)) {
                $blocking[] = (string) ($database['message'] ?? 'Database chưa sẵn sàng để bật Module.');
            }

            if (! ($permission['can_execute'] ?? false)) {
                $blocking[] = (string) ($permission['message'] ?? 'Permission manifest chưa sẵn sàng.');
            }
        } elseif ($usedBy !== []) {
            $blocking[] = 'Cần tắt các Module đang phụ thuộc trước: '.implode(', ', $usedBy).'.';
        }

        return [
            'module' => $moduleName,
            'current_enabled' => (bool) ($module['enabled'] ?? false),
            'target_enabled' => $targetEnabled,
            'action' => $targetEnabled ? 'enable' : 'disable',
            'action_label' => $targetEnabled ? 'Bật Module' : 'Tắt Module',
            'required' => (bool) ($module['required'] ?? false),
            'dependencies' => $dependencies,
            'used_by' => $usedBy,
            'database' => $database,
            'permission' => $permission,
            'queues' => $queues,
            'blocking' => $blocking,
            'can_execute' => $blocking === [],
        ];
    }

    private function databasePreview(array $module, bool $targetEnabled): array
    {
        if (! $targetEnabled) {
            return [
                'status' => 'not_required',
                'label' => 'Không thay đổi schema',
                'can_execute' => true,
                'expected_tables' => [],
                'missing_tables' => [],
                'missing_migration_records' => [],
                'message' => 'Tắt Module không rollback hoặc xóa bảng dữ liệu.',
            ];
        }

        try {
            $diagnosis = $this->lifecycle->migrationDiagnosis($module);
            $data = $diagnosis->toArray();

            if ($diagnosis->needsRecovery()) {
                return [
                    'status' => 'blocked',
                    'label' => 'Cần phục hồi migration',
                    'can_execute' => false,
                    'expected_tables' => $data['expected_tables'],
                    'missing_tables' => $data['missing_tables'],
                    'missing_migration_records' => $data['missing_migration_records'],
                    'message' => 'Migration ledger và schema chưa đồng bộ. Hãy chẩn đoán/phục hồi migration trước khi bật Module.',
                ];
            }

            if ($diagnosis->isReady()) {
                return [
                    'status' => 'ready',
                    'label' => 'Database sẵn sàng',
                    'can_execute' => true,
                    'expected_tables' => $data['expected_tables'],
                    'missing_tables' => [],
                    'missing_migration_records' => [],
                    'message' => 'Không cần chạy migration mới.',
                ];
            }

            return [
                'status' => 'migration_planned',
                'label' => 'Sẽ chạy migration',
                'can_execute' => true,
                'expected_tables' => $data['expected_tables'],
                'missing_tables' => $data['missing_tables'],
                'missing_migration_records' => $data['missing_migration_records'],
                'message' => $diagnosis->isResumable()
                    ? 'Migration đang ở trạng thái có thể tiếp tục an toàn; hệ thống sẽ chạy phần còn thiếu trước khi bật.'
                    : 'Database chưa có đầy đủ schema; hệ thống sẽ chạy migration trước khi bật.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'label' => 'Không kiểm tra được database',
                'can_execute' => false,
                'expected_tables' => [],
                'missing_tables' => [],
                'missing_migration_records' => [],
                'message' => 'Không đọc được trạng thái migration. Hãy kiểm tra log hệ thống và kết nối database.',
                'exception' => $e::class,
            ];
        }
    }

    private function permissionPreview(string $moduleName, bool $targetEnabled): array
    {
        if (! $targetEnabled) {
            return [
                'status' => 'cache_refresh',
                'label' => 'Refresh permission cache',
                'can_execute' => true,
                'permission_count' => 0,
                'message' => 'Permission records được giữ lại; cache quyền sẽ được làm mới sau khi tắt.',
            ];
        }

        try {
            $audit = collect($this->permissions->discoverModules())->firstWhere('name', $moduleName);

            if (! is_array($audit)) {
                return [
                    'status' => 'blocked',
                    'label' => 'Không tìm thấy permission manifest',
                    'can_execute' => false,
                    'permission_count' => 0,
                    'message' => 'Không tìm thấy Module trong permission discovery.',
                ];
            }

            $status = (string) ($audit['status'] ?? 'ok');
            $blocked = in_array($status, ['missing_registry', 'missing_manifest', 'missing_permissions'], true);

            return [
                'status' => $blocked ? 'blocked' : 'ready',
                'label' => $blocked ? 'Permission chưa sẵn sàng' : 'Permission sẵn sàng',
                'can_execute' => ! $blocked,
                'permission_count' => (int) ($audit['permission_count'] ?? 0),
                'permissions_required' => (bool) ($audit['permissions_required'] ?? false),
                'message' => match ($status) {
                    'missing_manifest' => 'Thiếu config/module.php nên không thể xác minh permission.',
                    'missing_permissions' => 'Module yêu cầu permission nhưng manifest chưa khai báo permission.',
                    'missing_registry' => 'Module chưa được đăng ký trong registry.',
                    'no_permission_required' => 'Module không yêu cầu permission riêng.',
                    default => 'Permission sẽ được đồng bộ trước khi runtime state được bật.',
                },
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'label' => 'Không kiểm tra được permission',
                'can_execute' => false,
                'permission_count' => 0,
                'message' => 'Không đọc được permission manifest. Hãy kiểm tra log hệ thống.',
                'exception' => $e::class,
            ];
        }
    }

    private function queuePreview(array $module): array
    {
        $definitions = $this->moduleQueueDefinitions($module);
        $defaultQueue = (string) config('queue.connections.'.config('queue.default').'.queue', 'default');

        return collect($definitions)
            ->filter(fn (array $queue): bool => $queue['name'] !== $defaultQueue)
            ->map(function (array $queue): array {
                $pending = Schema::hasTable('jobs')
                    ? DB::table('jobs')->where('queue', $queue['name'])->whereNull('reserved_at')->count()
                    : 0;
                $reserved = Schema::hasTable('jobs')
                    ? DB::table('jobs')->where('queue', $queue['name'])->whereNotNull('reserved_at')->count()
                    : 0;
                $failed = Schema::hasTable('failed_jobs')
                    ? DB::table('failed_jobs')->where('queue', $queue['name'])->count()
                    : 0;

                return $queue + [
                    'pending' => $pending,
                    'reserved' => $reserved,
                    'failed' => $failed,
                ];
            })
            ->values()
            ->all();
    }

    private function moduleQueueDefinitions(array $module): array
    {
        $path = rtrim((string) ($module['path'] ?? ''), '/\\');

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
                ->map(fn (array $definition): array => [
                    'name' => trim((string) $definition['name']),
                    'description' => (string) ($definition['description'] ?? ''),
                ])
                ->filter(fn (array $definition): bool => $definition['name'] !== '')
                ->unique('name')
                ->values()
                ->all();
        }

        return [];
    }
}
