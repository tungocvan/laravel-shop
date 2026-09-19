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
        $this->assertStringContainsString("'schema' => \$capture['schema']", $service);
        $this->assertStringContainsString("'related_data' => \$capture['related_data']", $service);
        $this->assertStringContainsString("'checksums.json'", $service);
        $this->assertStringContainsString('self::LEGACY_FORMAT_VERSION', $service);
        $this->assertStringContainsString('$this->data->compatibility($manifest, $expectedTables)', $service);
        $this->assertStringContainsString("'restore_tables'", $service);
        $this->assertStringContainsString("\$this->create(\$module, 'safety')", $service);
        $this->assertStringContainsString('flock($lock, LOCK_EX | LOCK_NB)', $service);
        $this->assertStringContainsString('Module restore and automatic rollback both failed.', $service);
        $this->assertStringContainsString("preg_replace('/\\sAUTO_INCREMENT=\\d+\\b/i'", $service);
        $this->assertStringContainsString("array_flip(['relative_path', 'absolute_path'])", $service);
        $this->assertStringContainsString("'compatibility_report' => \$compatibilityReport", $service);
        $this->assertStringContainsString("\$this->data->restore(\$snapshot['absolute_path'], \$validated['manifest'])", $service);
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
        $this->assertStringContainsString('public array $selectedModulePreflight = [];', $component);
        $this->assertStringContainsString("'compatibility_report'", $component);
        $this->assertStringContainsString('app(ModuleDependencyService::class)->dependenciesFor($this->moduleFilter)', $component);
        $this->assertStringContainsString("view('System::livewire.database.table-list-with-dependencies'", $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('DEPENDENCY WARNING', $dependencyView);
        $this->assertStringContainsString("implode(' · ', \$moduleDependencies)", $dependencyView);
        $this->assertStringContainsString('không được tự động backup hoặc restore toàn bộ', $dependencyView);
        $this->assertStringContainsString('related data', $dependencyView);
        $this->assertStringContainsString("@include('System::livewire.database.table-list')", $dependencyView);
        $this->assertStringContainsString('Xác nhận phạm vi Backup Module {{ $moduleFilter }}', $dependencyView);
        $this->assertStringContainsString('toàn bộ bảng thuộc ownership của Module {{ $moduleFilter }}', $dependencyView);
        $this->assertStringContainsString('Shared table chỉ lấy các row liên quan', $dependencyView);
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
        $this->assertStringContainsString('Preflight Restore:', $view);
        $this->assertStringContainsString('Shared table không được backup toàn bảng', $view);
    }

    public function test_module_snapshot_v2_is_schema_aware_and_supports_related_data_graphs(): void
    {
        $data = file_get_contents(base_path('Modules/System/Services/Database/ModuleSnapshotDataService.php'));
        $pharma = file_get_contents(base_path('Modules/Pharma/config/module.php'));

        $this->assertIsString($data);
        $this->assertIsString($pharma);
        $this->assertStringContainsString('information_schema.COLUMNS', $data);
        $this->assertStringContainsString('array_intersect_key($row, array_flip($columns))', $data);
        $this->assertStringContainsString('Cột mới sẽ dùng default/null của production.', $data);
        $this->assertStringContainsString('Cột cũ không còn tồn tại và sẽ được bỏ qua.', $data);
        $this->assertStringContainsString('Bảng mới của Module không có trong snapshot cũ; bảng hiện tại sẽ được giữ nguyên.', $data);
        $this->assertStringContainsString('data/owned/', $data);
        $this->assertStringContainsString('data/related/', $data);
        $this->assertStringContainsString("'name' => 'hssp_dossier_engine'", $pharma);
        $this->assertStringContainsString("'owner_table' => 'pharma_medicine_profiles'", $pharma);
        $this->assertStringContainsString("'table' => 'dossiers'", $pharma);
        $this->assertStringContainsString("'table' => 'dossier_items'", $pharma);
        $this->assertStringContainsString("'table' => 'dossier_attachments'", $pharma);
        $this->assertStringContainsString("'target_table' => 'dossier_templates'", $pharma);
        $this->assertStringContainsString("'target_table' => 'dossier_template_items'", $pharma);
        $this->assertStringContainsString('assertRelatedTableColumns', $data);
        $this->assertStringContainsString('khai báo bảng không tồn tại', $data);
        $this->assertStringContainsString('khai báo cột không tồn tại', $data);
        $this->assertStringNotContainsString("return ['name' => \$name, 'tables' => [], 'row_counts' => []];", $data);
        $this->assertStringContainsString('assertRelatedRestoreIdentityConflicts', $data);
        $this->assertStringContainsString('xung đột ID', $data);
        $this->assertStringContainsString('$existingRootIds', $data);
        $this->assertStringContainsString('whereIn($foreignKey, $existingRootIds)->delete()', $data);
    }

    public function test_canonical_validator_keeps_v2_out_of_legacy_sql_fallback(): void
    {
        $canonical = file_get_contents(base_path('Modules/System/Services/Database/CanonicalModuleSnapshotService.php'));

        $this->assertIsString($canonical);
        $this->assertStringContainsString("\$formatVersion === '2.0'", $canonical);
        $this->assertStringContainsString("'compatibility_basis'] = 'schema_aware_v2'", $canonical);
        $this->assertStringContainsString("if (\$enforceSchema && \$validated['compatibility'] === 'BLOCKED')", $canonical);
        $this->assertStringContainsString("if (\$validated['compatibility'] !== 'COMPATIBLE')", $canonical);
        $this->assertStringContainsString('\$this->readVerifiedSql(\$path)', $canonical);

        $v2Branch = strstr($canonical, "if (\$formatVersion === '2.0')", true);
        $this->assertIsString($v2Branch);
        $this->assertStringNotContainsString('readVerifiedSql', $v2Branch);
    }

}
