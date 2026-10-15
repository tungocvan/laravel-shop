<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Tests\TestCase;

class FullBackupSqlUploadContractTest extends TestCase
{
    public function test_unified_workspace_has_permission_gated_sql_upload(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/FullBackupWorkspace.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/full-backup-workspace.blade.php'));

        self::assertIsString($component);
        self::assertIsString($view);
        self::assertStringContainsString('use AuthorizesSystemActions, WithFileUploads;', $component);
        self::assertStringContainsString('public function uploadSql(DatabaseService $service)', $component);
        self::assertStringContainsString("\$this->authorizePermission('database.backup');", $component);
        self::assertStringContainsString("'extensions:sql'", $component);
        self::assertStringContainsString("'max:61440'", $component);
        self::assertStringContainsString('importBackupFile($file->getRealPath(), $file->getClientOriginalName())', $component);
        self::assertStringContainsString('wire:click="openUpload"', $view);
        self::assertStringContainsString('wire:submit="uploadSql"', $view);
        self::assertStringContainsString('wire:model="sqlFile"', $view);
        self::assertStringContainsString('LOCAL ONLY', $view);
    }

    public function test_upload_only_stages_validated_sql_and_restore_still_requires_separate_confirmation(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/FullBackupWorkspace.php'));
        $service = file_get_contents(base_path('Modules/System/Services/DatabaseService.php'));

        self::assertIsString($component);
        self::assertIsString($service);
        self::assertStringContainsString('public function openRestore', $component);
        self::assertStringContainsString("\$this->authorizePermission('database.restore');", $component);
        self::assertStringContainsString('public function confirmRestore', $component);
        self::assertStringContainsString('restoreFromFile($this->restoreReference)', $component);
        self::assertStringContainsString('isFullDatabaseBackup($sourcePath)', $service);
        self::assertStringContainsString('isFullDatabaseBackup($temporary)', $service);
        self::assertStringContainsString('bin2hex(random_bytes(4))', $service);
        self::assertStringContainsString('rename($temporary, $destination)', $service);
        self::assertStringContainsString('db_backup_before_restore_', $service);
    }
}
