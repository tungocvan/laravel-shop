<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Tests\TestCase;

class FullBackupWorkspaceContractTest extends TestCase
{
    public function test_database_page_uses_unified_full_backup_workspace(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/database.blade.php'));
        self::assertIsString($view);
        self::assertStringContainsString("@livewire('system.database.full-backup-workspace')", $view);
        self::assertStringContainsString('Backup Catalog · Restore · Local ↔ Google Drive', $view);
    }

    public function test_workspace_supports_catalog_sync_restore_and_scoped_delete(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/FullBackupWorkspace.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/full-backup-workspace.blade.php'));
        self::assertIsString($component);
        self::assertIsString($view);

        self::assertStringContainsString('createFullDatabaseBackup', $component);
        self::assertStringContainsString('UploadDatabaseBackupToGoogleDrive::dispatch', $component);
        self::assertStringContainsString('downloadRemote', $component);
        self::assertStringContainsString('importBackupFile', $component);
        self::assertStringContainsString('restoreFromFile', $component);
        self::assertStringContainsString('showRestoreModal', $component);
        self::assertStringContainsString('deleteLocal', $component);
        self::assertStringContainsString('deleteDrive', $component);

        self::assertStringContainsString('LOCAL + DRIVE', $view);
        self::assertStringContainsString('LOCAL ONLY', $view);
        self::assertStringContainsString('DRIVE ONLY', $view);
        self::assertStringContainsString('Xóa file đã chọn', $view);
        self::assertStringContainsString('Safety Backup được tạo tự động', $view);
        self::assertStringContainsString('Tải về Local', $view);
        self::assertStringContainsString('Upload Drive', $view);
    }

    public function test_restore_remains_local_only_and_database_service_creates_safety_backup(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/FullBackupWorkspace.php'));
        $service = file_get_contents(base_path('Modules/System/Services/DatabaseService.php'));
        self::assertIsString($component);
        self::assertIsString($service);
        self::assertStringContainsString("getBackupDescriptor(\$reference, ['sql'])", $component);
        self::assertStringContainsString('db_backup_before_restore_', $service);
        self::assertStringContainsString('dumpAtomically([], $safetyRelativePath, 300)', $service);
        self::assertStringContainsString('runMysqlImport($safetyPath, 600)', $service);
    }
}
