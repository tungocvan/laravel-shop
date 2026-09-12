<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Database\DatabaseBackupCatalogService;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupBulkManageTest extends TestCase
{
    public function test_local_backup_can_be_renamed_through_trusted_reference(): void
    {
        Storage::fake('local');
        config()->set('app.key', 'base64:test-system-backup-key');

        $sql = "-- MySQL dump\nDROP TABLE IF EXISTS `a`;\nCREATE TABLE `a` (`id` int);\nDROP TABLE IF EXISTS `b`;\nCREATE TABLE `b` (`id` int);\n";
        Storage::disk('local')->put('private/backups/original.sql', $sql);

        $catalog = app(DatabaseBackupCatalogService::class);
        $reference = $catalog->referenceForFileName('original.sql', ['sql']);

        $this->assertNotNull($reference);

        $renamed = $catalog->renameReference($reference, 'renamed-backup');

        $this->assertSame('renamed-backup.sql', $renamed['name']);
        Storage::disk('local')->assertMissing('private/backups/original.sql');
        Storage::disk('local')->assertExists('private/backups/renamed-backup.sql');
        $this->assertNull($catalog->resolveReference($reference, ['sql']));
    }

    public function test_local_backup_rename_rejects_duplicate_and_path_traversal(): void
    {
        Storage::fake('local');
        config()->set('app.key', 'base64:test-system-backup-key');

        $sql = "-- MySQL dump\nDROP TABLE IF EXISTS `a`;\nCREATE TABLE `a` (`id` int);\nDROP TABLE IF EXISTS `b`;\nCREATE TABLE `b` (`id` int);\n";
        Storage::disk('local')->put('private/backups/first.sql', $sql);
        Storage::disk('local')->put('private/backups/existing.sql', $sql);

        $catalog = app(DatabaseBackupCatalogService::class);
        $reference = $catalog->referenceForFileName('first.sql', ['sql']);

        try {
            $catalog->renameReference((string) $reference, 'existing');
            $this->fail('Expected duplicate backup name to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('đã tồn tại', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $catalog->renameReference((string) $reference, '../outside');
    }

    public function test_bulk_management_contract_is_present_for_local_and_drive_backups(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/BackupManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/backup-manager.blade.php'));
        $drive = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDriveBackupBrowserService.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($drive);

        foreach (['selectedLocalBackups', 'selectedRemoteBackups', 'confirmBulkDelete', 'submitRename'] as $needle) {
            $this->assertStringContainsString($needle, $component);
        }

        $this->assertStringContainsString('wire:model.live="selectedLocalBackups"', $view);
        $this->assertStringContainsString('wire:model.live="selectedRemoteBackups"', $view);
        $this->assertStringContainsString("openBulkDeleteModal('local')", $view);
        $this->assertStringContainsString("openBulkDeleteModal('remote')", $view);
        $this->assertStringContainsString('Xác nhận xóa', $view);
        $this->assertStringContainsString('public function rename(string $reference, string $requestedName): array', $drive);
        $this->assertStringContainsString("->patch('https://www.googleapis.com/drive/v3/files/'", $drive);
    }
}
