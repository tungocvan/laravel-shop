<?php

namespace App\Modules;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RequestReleaseReadinessChecker
{
    public function __construct(
        private readonly ModuleLifecycleManager $lifecycle,
        private readonly ModuleStateResolver $stateResolver,
    ) {}

    public function check(): array
    {
        $path = base_path('Modules/Request');
        $manifest = require $path.'/config/module.php';
        $files = require $path.'/config/files.php';
        $exports = require $path.'/config/exports.php';
        $notifications = require $path.'/config/notifications.php';
        $module = ['name' => 'Request', 'path' => $path];

        $state = $this->stateResolver->resolve('Request', $manifest, 'manifest', false);
        $diagnosis = $this->lifecycle->migrationDiagnosis($module);
        $permissions = collect($manifest['permissions'] ?? [])->map('strval')->values();
        $existingPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $permissions)
            ->pluck('name');
        $superAdmin = Role::query()->where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        $assignedPermissions = $superAdmin
            ? $superAdmin->permissions->pluck('name')->intersect($permissions)
            : collect();
        $disk = (string) ($files['disk'] ?? '');
        $diskConfigured = $disk !== '' && config("filesystems.disks.{$disk}") !== null;
        $privateDisk = $disk !== '' && $disk !== 'public' && $diskConfigured;
        $queuesReady = ($notifications['outbox_queue'] ?? null) === 'request-outbox'
            && ($notifications['queue'] ?? null) === 'request-notifications'
            && ($exports['queue'] ?? null) === 'request-exports';

        $checks = [
            'module_enabled' => [
                'passed' => (bool) $state['enabled'],
                'detail' => $state['enabled'] ? 'Request đang bật.' : 'Request đang tắt.',
            ],
            'migration_ready' => [
                'passed' => $diagnosis->isReady(),
                'detail' => sprintf(
                    '%d/%d bảng, %d/%d migration ledger.',
                    count($diagnosis->existingTables),
                    count($diagnosis->expectedTables),
                    count($diagnosis->recordedMigrations),
                    count($diagnosis->migrationFiles),
                ),
            ],
            'permissions_synced' => [
                'passed' => $existingPermissions->count() === $permissions->count(),
                'detail' => sprintf('%d/%d permission đã tồn tại.', $existingPermissions->count(), $permissions->count()),
            ],
            'super_admin_permissions' => [
                'passed' => $assignedPermissions->count() === $permissions->count(),
                'detail' => sprintf('%d/%d permission đã gán cho Super Admin.', $assignedPermissions->count(), $permissions->count()),
            ],
            'private_storage' => [
                'passed' => $privateDisk,
                'detail' => $disk === '' ? 'Chưa cấu hình disk.' : "Disk: {$disk}.",
            ],
            'queue_contract' => [
                'passed' => $queuesReady,
                'detail' => sprintf(
                    'outbox=%s, notifications=%s, exports=%s.',
                    (string) ($notifications['outbox_queue'] ?? ''),
                    (string) ($notifications['queue'] ?? ''),
                    (string) ($exports['queue'] ?? ''),
                ),
            ],
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['passed']),
            'checks' => $checks,
        ];
    }
}
