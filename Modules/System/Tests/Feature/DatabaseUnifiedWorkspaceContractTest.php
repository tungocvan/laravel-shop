<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Tests\TestCase;

class DatabaseUnifiedWorkspaceContractTest extends TestCase
{
    public function test_database_workspace_is_module_first_and_lazy_renders_table_workspace(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/database.blade.php'));
        self::assertIsString($view);
        self::assertStringContainsString('Chọn Module cần Backup / Restore', $view);
        self::assertStringContainsString('loadingModule = true', $view);
        self::assertStringContainsString('Đang tải Module…', $view);
        self::assertStringContainsString("@if (\$selectedModule === '')", $view);
        self::assertStringContainsString("['moduleFilter' => \$selectedModule]", $view);
        self::assertStringContainsString('Danh sách bảng được ẩn mặc định', $view);
    }

    public function test_unified_workspace_preserves_full_database_and_drive_manager(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/database.blade.php'));
        self::assertIsString($view);
        self::assertStringContainsString('Toàn bộ Database · Backup / Restore · Google Drive', $view);
        self::assertStringContainsString("@livewire('system.database.backup-manager')", $view);
        self::assertStringContainsString('đồng bộ Google Drive hai chiều', $view);

        $manager = file_get_contents(base_path('Modules/System/Livewire/Database/BackupManager.php'));
        self::assertIsString($manager);
        self::assertStringContainsString('GoogleDriveConnectionService', $manager);
        self::assertStringContainsString('GoogleDriveBackupBrowserService', $manager);
        self::assertStringContainsString('UploadDatabaseBackupToGoogleDrive', $manager);
    }

    public function test_legacy_backup_restore_route_redirects_to_database_workspace(): void
    {
        $routes = file_get_contents(base_path('Modules/System/routes/web.php'));
        self::assertIsString($routes);
        self::assertStringContainsString("Route::redirect('/backup-restore', '/admin/system/database')", $routes);
        self::assertStringContainsString("->name('backup-restore')", $routes);
    }

    public function test_database_controller_limits_workspace_to_known_module(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/DatabaseController.php'));
        self::assertIsString($controller);
        self::assertStringContainsString("\$request->query('module', '')", $controller);
        self::assertStringContainsString("\$module !== 'Unknown'", $controller);
        self::assertStringContainsString('in_array($selectedModule, $modules, true)', $controller);
    }
}
