<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Cloud\GoogleDriveBackupBrowserService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Modules\System\Services\Database\DatabaseBackupCatalogService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SystemDriveBackupBoundaryTest extends TestCase
{
    #[Test]
    public function local_backup_catalog_only_resolves_server_issued_references(): void
    {
        Storage::fake('local');
        config(['app.key' => 'base64:test-backup-reference-key']);

        $sql = "-- MySQL dump\nDROP TABLE IF EXISTS `first`;\nCREATE TABLE `first` (`id` int);\n"
            ."DROP TABLE IF EXISTS `second`;\nCREATE TABLE `second` (`id` int);\n"
            .str_repeat("-- padding\n", 20);
        $relativePath = 'private/backups/db_backup_full_contract.sql';
        Storage::disk('local')->put($relativePath, $sql);

        $catalog = app(DatabaseBackupCatalogService::class);
        $backups = $catalog->listBackups();

        $this->assertCount(1, $backups);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $backups[0]['id']);
        $this->assertSame($backups[0]['id'], $backups[0]['path']);
        $this->assertArrayNotHasKey('absolute_path', $backups[0]);
        $this->assertArrayNotHasKey('relative_path', $backups[0]);
        $this->assertNull($catalog->resolveReference('db_backup_full_contract.sql'));
        $this->assertNull($catalog->resolveReference('../private/backups/db_backup_full_contract.sql'));
        $this->assertSame($relativePath, $catalog->resolveReference($backups[0]['id'])['relative_path']);

        $catalog->deleteReference($backups[0]['id']);
        Storage::disk('local')->assertMissing($relativePath);
    }

    #[Test]
    public function remote_backup_browser_rejects_raw_drive_ids_before_network_access(): void
    {
        $drive = $this->createMock(GoogleDriveConnectionService::class);
        $drive->expects($this->never())->method('accessToken');
        $browser = new GoogleDriveBackupBrowserService($drive);

        $this->expectException(RuntimeException::class);
        $browser->download('raw-google-drive-file-id', storage_path('framework/unused.sql'));
    }

    #[Test]
    public function legacy_public_url_restore_shortcuts_are_retired(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/BackupManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/backup-manager.blade.php'));
        $automation = file_get_contents(base_path('Modules/System/Services/Cloud/CloudBackupAutomationService.php'));
        $database = file_get_contents(base_path('Modules/System/Services/DatabaseService.php'));
        $oauth = file_get_contents(base_path('Modules/System/Http/Controllers/GoogleDriveOAuthController.php'));

        $this->assertStringNotContainsString('drive.usercontent.google.com', $component);
        $this->assertStringNotContainsString('restoreFromGoogleDrive', $component);
        $this->assertStringNotContainsString('importFromGoogleDrive', $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);
        $this->assertStringNotContainsString("get('cloud.google_drive.auto.last_message'", $automation);
        $this->assertStringNotContainsString('getErrorOutput()', $database);
        $this->assertStringNotContainsString("'error' => \$e->getMessage()", $oauth);
        $this->assertStringContainsString("'.partial-'", $database);
        $this->assertStringContainsString('rename($temporaryPath, $finalPath)', $database);
        $this->assertStringNotContainsString('wire:poll', $view);
        $this->assertStringContainsString('Tải về Local', $view);
        $this->assertStringContainsString('Restore được xác nhận riêng', file_get_contents(base_path('Modules/System/resources/views/livewire/settings/storage-config.blade.php')));
    }
}
