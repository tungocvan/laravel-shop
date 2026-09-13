<?php

namespace Modules\System\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\System\Data\SystemDashboardData;
use Throwable;

final class SystemDashboardService
{
    private const SETTINGS_KEYS = [
        'cloud.google_drive.refresh_token',
        'cloud.google_drive.auto.enabled',
        'cloud.google_drive.auto.last_status',
        'cloud.google_drive.auto.last_run_at',
    ];

    private const WARNING_LIMIT = 5;

    public function forUser(mixed $user): SystemDashboardData
    {
        $capabilities = [
            'manage' => $this->hasPermission($user, 'system.manage'),
            'settings_view' => $this->hasPermission($user, 'system.settings.view'),
            'env_view' => $this->hasPermission($user, 'system.env.view'),
            'modules_view' => $this->hasPermission($user, 'system.modules.view'),
            'commands_run' => $this->hasPermission($user, 'system.commands.run'),
            'database_view' => $this->hasPermission($user, 'database.view'),
        ];

        $settingsVisible = $capabilities['settings_view'] || $capabilities['env_view'];
        $availability = [
            'database' => $capabilities['database_view'] && $this->tableExists('migrations'),
            'settings' => $settingsVisible && $this->tableExists('settings'),
            'jobs' => $capabilities['settings_view'] && $this->tableExists('jobs'),
            'failed_jobs' => $capabilities['settings_view'] && $this->tableExists('failed_jobs'),
        ];

        $settingsSnapshot = $this->settingsSnapshot($capabilities['env_view'], $availability['settings']);
        $configuration = $this->configurationMetrics($capabilities['env_view']);
        $queueMetrics = $this->queueMetrics(
            $capabilities['settings_view'],
            $availability['jobs'],
            $availability['failed_jobs'],
        );
        $drive = $this->driveStatus($capabilities['env_view'], $settingsSnapshot);
        $cloudBackup = $this->cloudBackupStatus($capabilities['env_view'], $settingsSnapshot);
        $workspaces = $this->workspaces($capabilities);
        $subsystems = [
            'settings' => [
                'visible' => $settingsVisible,
                'available' => $availability['settings'],
                'state' => ! $settingsVisible ? 'hidden' : ($availability['settings'] ? 'ready' : 'unavailable'),
            ],
            'queue' => [
                'visible' => $capabilities['settings_view'],
                'available' => $queueMetrics['available'],
                'state' => $this->queueState($queueMetrics),
            ],
            'database' => [
                'visible' => $capabilities['database_view'],
                'available' => $availability['database'],
                'state' => ! $capabilities['database_view'] ? 'hidden' : ($availability['database'] ? 'ready' : 'unavailable'),
            ],
            'google_drive' => $drive,
            'cloud_backup' => $cloudBackup,
        ];
        $warnings = $this->warnings(
            $capabilities,
            $availability,
            $configuration,
            $queueMetrics,
            $drive,
            $cloudBackup,
        );

        return new SystemDashboardData(
            generatedAt: now()->toIso8601String(),
            capabilities: $capabilities,
            availability: $availability,
            metrics: [
                'workspaces' => [
                    'visible' => count($workspaces),
                    'maximum' => 8,
                ],
                'configuration' => $configuration,
                'queues' => $queueMetrics,
                'warnings' => ['count' => count($warnings)],
            ],
            subsystems: $subsystems,
            workspaces: $workspaces,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, bool>  $capabilities
     * @return array<int, array{code: string, label: string, description: string, category: string}>
     */
    private function workspaces(array $capabilities): array
    {
        $definitions = [
            [
                'permission' => 'modules_view',
                'code' => 'modules',
                'label' => 'Quản lý Modules',
                'description' => 'Bật/tắt Module với dependency, migration, permission và queue impact preflight.',
                'category' => 'Vận hành',
            ],
            [
                'permission' => 'manage',
                'code' => 'queue',
                'label' => 'Queue Manager',
                'description' => 'Theo dõi worker, pending/failed jobs, probe health và Realtime runtime.',
                'category' => 'Vận hành',
            ],
            [
                'permission' => 'database_view',
                'code' => 'database',
                'label' => 'Database Manager',
                'description' => 'Quản lý dữ liệu và metadata database trong workspace có authorization riêng.',
                'category' => 'Dữ liệu & Khôi phục',
            ],
            [
                'permission' => 'database_view',
                'code' => 'backup',
                'label' => 'Backup / Restore',
                'description' => 'Quản lý backup, Module Snapshot và khôi phục có safety boundary.',
                'category' => 'Dữ liệu & Khôi phục',
            ],
            [
                'permission' => 'env_view',
                'code' => 'environment',
                'label' => 'Môi trường & Tích hợp',
                'description' => 'Database connection, Email, Storage / Cloud, Web & Analytics và Runtime & Bridge.',
                'category' => 'Cấu hình hạ tầng',
            ],
            [
                'permission' => 'settings_view',
                'code' => 'login',
                'label' => 'Giao diện & Đăng nhập',
                'description' => 'Quản lý branding đăng nhập, hành vi sau đăng nhập và điều hướng mặc định.',
                'category' => 'Quản trị truy cập',
            ],
            [
                'permission' => 'commands_run',
                'code' => 'artisan',
                'label' => 'Artisan',
                'description' => 'Chạy các thao tác Artisan đã được giới hạn phía server.',
                'category' => 'Công cụ kỹ thuật',
            ],
            [
                'permission' => 'commands_run',
                'code' => 'scripts',
                'label' => 'Scripts',
                'description' => 'Chạy registry script vận hành đã được giới hạn phía server.',
                'category' => 'Công cụ kỹ thuật',
            ],
        ];

        return collect($definitions)
            ->filter(fn (array $workspace): bool => $capabilities[$workspace['permission']] ?? false)
            ->map(fn (array $workspace): array => [
                'code' => $workspace['code'],
                'label' => $workspace['label'],
                'description' => $workspace['description'],
                'category' => $workspace['category'],
            ])
            ->values()
            ->all();
    }

    private function configurationMetrics(bool $visible): array
    {
        if (! $visible) {
            return ['visible' => false, 'available' => false, 'ready' => null, 'total' => null];
        }

        try {
            $checks = [
                filled(config('app.key')),
                filled(config('database.default')),
                filled(config('mail.default')),
            ];

            return [
                'visible' => true,
                'available' => true,
                'ready' => count(array_filter($checks)),
                'total' => count($checks),
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('configuration', $exception);

            return ['visible' => true, 'available' => false, 'ready' => 0, 'total' => 3];
        }
    }

    private function queueMetrics(bool $visible, bool $jobsAvailable, bool $failedJobsAvailable): array
    {
        if (! $visible) {
            return ['visible' => false, 'available' => false, 'pending' => null, 'reserved' => null, 'failed' => null];
        }

        if (! $jobsAvailable || ! $failedJobsAvailable) {
            return ['visible' => true, 'available' => false, 'pending' => 0, 'reserved' => 0, 'failed' => 0];
        }

        try {
            $jobs = DB::table('jobs')
                ->selectRaw('SUM(CASE WHEN reserved_at IS NULL THEN 1 ELSE 0 END) as pending')
                ->selectRaw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as reserved')
                ->first();
            $failed = DB::table('failed_jobs')->count();

            return [
                'visible' => true,
                'available' => true,
                'pending' => max(0, (int) ($jobs?->pending ?? 0)),
                'reserved' => max(0, (int) ($jobs?->reserved ?? 0)),
                'failed' => max(0, $failed),
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('queue_metrics', $exception);

            return ['visible' => true, 'available' => false, 'pending' => 0, 'reserved' => 0, 'failed' => 0];
        }
    }

    private function driveStatus(bool $visible, array $settingsSnapshot): array
    {
        if (! $visible) {
            return ['visible' => false, 'available' => false, 'configured' => null, 'connected' => null, 'state' => 'hidden'];
        }

        $configured = filled(config('system.google_drive.client_id'))
            && filled(config('system.google_drive.client_secret'))
            && filled(config('system.google_drive.redirect_uri'));
        $connected = $settingsSnapshot['available']
            ? $this->hasEncryptedSecret($settingsSnapshot['values']['cloud.google_drive.refresh_token'] ?? '')
            : null;

        return [
            'visible' => true,
            'available' => $settingsSnapshot['available'],
            'configured' => $configured,
            'connected' => $connected,
            'state' => ! $settingsSnapshot['available'] ? 'unavailable' : ($configured && $connected ? 'ready' : 'attention'),
        ];
    }

    private function cloudBackupStatus(bool $visible, array $settingsSnapshot): array
    {
        if (! $visible) {
            return [
                'visible' => false,
                'available' => false,
                'enabled' => null,
                'last_status' => null,
                'last_run_at' => null,
                'state' => 'hidden',
            ];
        }

        if (! $settingsSnapshot['available']) {
            return [
                'visible' => true,
                'available' => false,
                'enabled' => null,
                'last_status' => null,
                'last_run_at' => null,
                'state' => 'unavailable',
            ];
        }

        $lastStatus = (string) ($settingsSnapshot['values']['cloud.google_drive.auto.last_status'] ?? '');
        $lastStatus = in_array($lastStatus, ['success', 'failed'], true) ? $lastStatus : null;
        $enabled = filter_var($settingsSnapshot['values']['cloud.google_drive.auto.enabled'] ?? false, FILTER_VALIDATE_BOOL);

        return [
            'visible' => true,
            'available' => true,
            'enabled' => $enabled,
            'last_status' => $lastStatus,
            'last_run_at' => $this->iso($settingsSnapshot['values']['cloud.google_drive.auto.last_run_at'] ?? null),
            'state' => $lastStatus === 'failed' ? 'danger' : ($enabled ? 'ready' : 'idle'),
        ];
    }

    private function settingsSnapshot(bool $visible, bool $settingsAvailable): array
    {
        if (! $visible || ! $settingsAvailable) {
            return ['available' => false, 'values' => []];
        }

        try {
            return [
                'available' => true,
                'values' => DB::table('settings')
                    ->select(['key', 'value'])
                    ->whereIn('key', self::SETTINGS_KEYS)
                    ->pluck('value', 'key')
                    ->map(fn (mixed $value): string => (string) $value)
                    ->all(),
            ];
        } catch (Throwable $exception) {
            $this->logUnavailable('settings_snapshot', $exception);

            return ['available' => false, 'values' => []];
        }
    }

    private function queueState(array $queueMetrics): string
    {
        if (! $queueMetrics['visible']) {
            return 'hidden';
        }

        if (! $queueMetrics['available']) {
            return 'unavailable';
        }

        if ($queueMetrics['pending'] === 0 && $queueMetrics['reserved'] === 0 && $queueMetrics['failed'] === 0) {
            return 'empty';
        }

        return $queueMetrics['failed'] > 0 ? 'danger' : 'ready';
    }

    private function warnings(
        array $capabilities,
        array $availability,
        array $configuration,
        array $queueMetrics,
        array $drive,
        array $cloudBackup,
    ): array {
        $warnings = [];

        if ($queueMetrics['visible'] && $queueMetrics['available'] && $queueMetrics['failed'] > 0) {
            $warnings[] = [
                'level' => 'danger',
                'code' => 'failed-jobs',
                'message' => number_format($queueMetrics['failed']).' job đang ở trạng thái thất bại.',
            ];
        }

        if ($cloudBackup['visible'] && $cloudBackup['last_status'] === 'failed') {
            $warnings[] = [
                'level' => 'danger',
                'code' => 'cloud-backup-failed',
                'message' => 'Lần cloud backup gần nhất thất bại. Hãy kiểm tra tại workspace lưu trữ.',
            ];
        }

        if (($capabilities['settings_view'] || $capabilities['env_view']) && ! $availability['settings']) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'settings-unavailable',
                'message' => 'Kho thiết lập hệ thống chưa sẵn sàng. Hãy kiểm tra migration.',
            ];
        }

        if ($capabilities['database_view'] && ! $availability['database']) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'database-unavailable',
                'message' => 'Metadata database chưa sẵn sàng tại thời điểm tải Dashboard.',
            ];
        }

        if ($queueMetrics['visible'] && ! $queueMetrics['available']) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'queue-unavailable',
                'message' => 'Kho queue chưa đầy đủ nên chưa thể tổng hợp trạng thái job.',
            ];
        }

        if ($configuration['visible'] && $configuration['available'] && $configuration['ready'] < $configuration['total']) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'configuration-incomplete',
                'message' => 'Một hoặc nhiều nhóm cấu hình runtime bắt buộc chưa hoàn chỉnh.',
            ];
        }

        if ($drive['visible'] && $drive['available'] && (! $drive['configured'] || ! $drive['connected'])) {
            $warnings[] = [
                'level' => 'warning',
                'code' => 'google-drive-attention',
                'message' => 'Google Drive chưa được cấu hình và kết nối đầy đủ.',
            ];
        }

        return array_slice($warnings, 0, self::WARNING_LIMIT);
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable $exception) {
            $this->logUnavailable('table_'.$table, $exception);

            return false;
        }
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        try {
            if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin', 'admin')) {
                return true;
            }

            return method_exists($user, 'checkPermissionTo') && $user->checkPermissionTo($permission, 'admin');
        } catch (Throwable) {
            return false;
        }
    }

    private function hasEncryptedSecret(string $encrypted): bool
    {
        if ($encrypted === '') {
            return false;
        }

        try {
            return filled(Crypt::decryptString($encrypted));
        } catch (Throwable) {
            return false;
        }
    }

    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    private function logUnavailable(string $section, Throwable $exception): void
    {
        Log::warning('System Dashboard section is unavailable.', [
            'section' => $section,
            'exception_class' => $exception::class,
        ]);
    }
}
