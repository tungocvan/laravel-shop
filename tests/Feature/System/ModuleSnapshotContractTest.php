<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class ModuleSnapshotContractTest extends TestCase
{
    public function test_module_snapshot_service_has_manifest_checksum_ownership_and_rollback_guards(): void
    {
        $service = file_get_contents(base_path('Modules/System/Services/Database/ModuleSnapshotService.php'));

        $this->assertIsString($service);
        $this->assertStringContainsString("'format_version' => self::FORMAT_VERSION", $service);
        $this->assertStringContainsString("'dependencies' => \$this->dependencies->dependenciesFor(\$module)", $service);
        $this->assertStringContainsString("(array) (\$manifest['dependencies'] ?? [])", $service);
        $this->assertStringContainsString("'schema_fingerprint' => \$this->schemaFingerprint(\$tables)", $service);
        $this->assertStringContainsString("'checksums.json'", $service);
        $this->assertStringContainsString("hash_equals(\$expectedChecksum, hash('sha256', \$sql))", $service);
        $this->assertStringContainsString("\$snapshotTables !== \$expectedTables", $service);
        $this->assertStringContainsString("\$this->create(\$module, 'safety')", $service);
        $this->assertStringContainsString('flock($lock, LOCK_EX | LOCK_NB)', $service);
        $this->assertStringContainsString('Module restore and automatic rollback both failed.', $service);
        $this->assertStringContainsString("preg_replace('/\\sAUTO_INCREMENT=\\d+\\b/i'", $service);
        $this->assertStringContainsString("array_flip(['relative_path', 'absolute_path'])", $service);
        $this->assertStringContainsString("'--single-transaction'", $service);
        $this->assertStringContainsString("'--skip-lock-tables'", $service);
    }

    public function test_module_dependency_service_uses_registry_as_single_dependency_source(): void
    {
        $dependency = file_get_contents(base_path('Modules/System/Services/Database/ModuleDependencyService.php'));

        $this->assertIsString($dependency);
        $this->assertStringContainsString('use App\\Modules\\ModuleRegistry;', $dependency);
        $this->assertStringContainsString('private readonly ModuleRegistry $registry', $dependency);
        $this->assertStringContainsString('$this->registry->current()', $dependency);
        $this->assertStringContainsString("(array) (\$registered['depends'] ?? [])", $dependency);
        $this->assertStringNotContainsString('Schema::', $dependency);
        $this->assertStringNotContainsString('information_schema', $dependency);
    }

    public function test_module_snapshot_cloud_sync_is_scoped_to_module_namespace(): void
    {
        $cloud = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDriveModuleSnapshotService.php'));

        $this->assertIsString($cloud);
        $this->assertStringContainsString("ensureChildFolder(\$token, \$rootId, 'database')", $cloud);
        $this->assertStringContainsString("ensureChildFolder(\$token, \$databaseId, 'modules')", $cloud);
        $this->assertStringContainsString('ensureChildFolder($token, $modulesId, $module)', $cloud);
        $this->assertStringContainsString("'application/zip'", $cloud);
        $this->assertStringContainsString('downloadToLocal', $cloud);
        $this->assertStringContainsString('importDownloadedPackage', $cloud);
        $this->assertStringContainsString('public function delete(string $module, string $remoteReference): void', $cloud);
        $this->assertStringNotContainsString('restore(', $cloud);
    }

    public function test_local_module_snapshot_deletion_stays_inside_trusted_snapshot_root(): void
    {
        $deletion = file_get_contents(base_path('Modules/System/Services/Database/ModuleSnapshotDeletionService.php'));

        $this->assertIsString($deletion);
        $this->assertStringContainsString('resolveLocalReference($reference, $module)', $deletion);
        $this->assertStringContainsString("storage_path('app/private/backups/modules')", $deletion);
        $this->assertStringContainsString('str_starts_with($trustedFile, $rootPrefix)', $deletion);
        $this->assertStringContainsString('unlink($trustedFile)', $deletion);
    }

    public function test_database_manager_exposes_module_snapshot_workflow_without_raw_exception_messages(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/TableList.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list.blade.php'));
        $dependencyView = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list-with-dependencies.blade.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($dependencyView);

        foreach ([
            'backupModule',
            'backupModuleAndUpload',
            'uploadModuleSnapshot',
            'downloadModuleSnapshot',
            'deleteLocalModuleSnapshot',
            'deleteRemoteModuleSnapshot',
            'openModuleRestoreModal',
            'restoreModuleSnapshot',
            'refreshModuleSnapshots',
        ] as $method) {
            $this->assertStringContainsString('function '.$method, $component);
        }

        $this->assertStringContainsString("authorizePermission('database.backup')", $component);
        $this->assertStringContainsString("authorizePermission('database.download')", $component);
        $this->assertStringContainsString("authorizePermission('database.restore')", $component);
        $this->assertStringContainsString("authorizePermission('database.destroy')", $component);
        $this->assertStringContainsString('public array $moduleDependencies = [];', $component);
        $this->assertStringContainsString('app(ModuleDependencyService::class)->dependenciesFor($this->moduleFilter)', $component);
        $this->assertStringContainsString("view('System::livewire.database.table-list-with-dependencies'", $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('DEPENDENCY WARNING', $dependencyView);
        $this->assertStringContainsString("implode(' · ', \$moduleDependencies)", $dependencyView);
        $this->assertStringContainsString('không được tự động backup hoặc restore', $dependencyView);
        $this->assertStringContainsString("@include('System::livewire.database.table-list')", $dependencyView);
        $this->assertStringContainsString('Xác nhận phạm vi Backup Module {{ $moduleFilter }}', $dependencyView);
        $this->assertStringContainsString('toàn bộ bảng thuộc ownership của Module {{ $moduleFilter }}', $dependencyView);
        $this->assertStringContainsString('Bộ lọc tìm kiếm và checkbox bảng đang hiển thị không làm thay đổi phạm vi Module Snapshot', $dependencyView);
        $this->assertStringContainsString('Đã hiểu, tiếp tục', $dependencyView);
        $this->assertStringContainsString('wire:key="module-backup-scope-{{ $moduleFilter }}"', $dependencyView);

        $this->assertStringContainsString('Module Snapshot — {{ $moduleFilter }}', $view);
        $this->assertStringContainsString('Backup Module', $view);
        $this->assertStringContainsString('Backup & Upload Drive', $view);
        $this->assertStringContainsString('LOCAL + DRIVE', $view);
        $this->assertStringContainsString('DRIVE ONLY', $view);
        $this->assertStringContainsString('Tải về Local', $view);
        $this->assertStringContainsString('Xóa Local', $view);
        $this->assertStringContainsString('Xóa Drive', $view);
        $this->assertStringContainsString('Bản Google Drive nếu có sẽ KHÔNG bị xóa', $view);
        $this->assertStringContainsString('Bản local nếu có sẽ KHÔNG bị xóa', $view);
        $this->assertStringContainsString('RESTORE MODULE', $view);
        $this->assertStringContainsString('Safety Snapshot', $view);
    }
}
