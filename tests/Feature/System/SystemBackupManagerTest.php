<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemBackupManagerTest extends TestCase
{
    public function test_backup_manager_security_and_ui_contract(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/BackupManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/backup-manager.blade.php'));
        $routes = file_get_contents(base_path('Modules/System/routes/web.php'));

        $this->assertStringContainsString('permission:database.view,admin', $routes);
        $this->assertStringContainsString('permission:database.download,admin', $routes);

        $this->assertGreaterThanOrEqual(2, substr_count($component, "authorizePermission('database.restore')"));
        $this->assertGreaterThanOrEqual(2, substr_count($component, "authorizePermission('database.destroy')"));
        $this->assertGreaterThanOrEqual(2, substr_count($component, "authorizePermission('database.download')"));

        $this->assertStringNotContainsString('message: $e->getMessage()', $component);
        $this->assertStringNotContainsString("addError('sqlFile', \$e->getMessage())", $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);
        $this->assertStringContainsString('reportOperationError(', $component);
        $this->assertStringContainsString('Log::error(', $component);

        $this->assertStringContainsString('private const RECENT_BACKUP_LIMIT = 50;', $component);
        $this->assertStringContainsString('array_slice($allBackups, 0, self::RECENT_BACKUP_LIMIT)', $component);
        $this->assertStringContainsString('backupHistoryTruncated', $component);
        $this->assertStringContainsString('backupHistoryLimit', $view);

        $this->assertStringNotContainsString('drive.usercontent.google.com', $component);
        $this->assertStringNotContainsString('restoreFromGoogleDrive', $component);
        $this->assertStringNotContainsString('importFromGoogleDrive', $component);
        $this->assertStringContainsString('downloadRemoteBackup(', $component);
        $this->assertStringContainsString("'reference'", file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDriveBackupBrowserService.php')));
        $this->assertStringContainsString('@unlink($temporaryPath)', $component);
        $this->assertStringContainsString('getDownloadPath(', $component);
        $this->assertStringContainsString('MAX_ATTACHMENT_BYTES', $component);
        $this->assertStringContainsString('SendDatabaseBackupEmail::dispatch', $component);

        $this->assertStringContainsString('wire:confirm', $view);
        $this->assertStringContainsString("route('admin.system.database.download'", $view);
        $this->assertStringNotContainsString('wire:poll', $view);
        $this->assertStringContainsString("\$remote['reference']", $view);
        $this->assertStringContainsString("\$file['id']", $view);
    }
}
