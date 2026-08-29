<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemGoogleDriveSchedulerTest extends TestCase
{
    #[Test]
    public function google_drive_redirect_uri_is_derived_from_app_url_when_not_explicitly_configured(): void
    {
        $source = file_get_contents(base_path('Modules/System/config/google_drive.php'));

        $this->assertStringContainsString("env('GOOGLE_DRIVE_REDIRECT_URI', '')", $source);
        $this->assertStringContainsString("env('APP_URL', '')", $source);
        $this->assertStringContainsString("'/admin/system/settings/cloud/google/callback'", $source);
        $this->assertStringContainsString("\$explicitRedirectUri !== ''", $source);
    }

    #[Test]
    public function google_drive_connection_and_scheduler_reuse_contracts_are_preserved(): void
    {
        $drive = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDriveConnectionService.php'));
        $browser = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDriveBackupBrowserService.php'));
        $storageConfig = file_get_contents(base_path('Modules/System/Livewire/Settings/StorageConfig.php'));
        $scheduler = file_get_contents(base_path('routes/console.php'));
        $job = file_get_contents(base_path('Modules/System/Jobs/UploadDatabaseBackupToGoogleDrive.php'));

        $this->assertStringContainsString("config('system.google_drive.redirect_uri')", $drive);
        $this->assertStringContainsString('public function status(): array', $drive);
        $this->assertStringContainsString('public function accessToken(): string', $drive);
        $this->assertStringContainsString("fopen(\$path, 'rb')", $drive);
        $this->assertStringContainsString("withBody(\$stream, 'application/sql')", $drive);
        $this->assertStringNotContainsString('file_get_contents($path)', $drive);

        $this->assertStringContainsString('private const MAX_LIST_LIMIT = 100;', $browser);
        $this->assertStringContainsString('private const MAX_YEAR_FOLDERS = 10;', $browser);
        $this->assertStringContainsString('private const MAX_MONTH_FOLDERS = 12;', $browser);
        $this->assertStringContainsString("preg_match('/\\A[a-f0-9]{64}\\z/'", $browser);

        $this->assertGreaterThanOrEqual(2, substr_count($storageConfig, "authorizePermission('database.backup')"));
        $this->assertGreaterThanOrEqual(2, substr_count($storageConfig, "authorizePermission('database.destroy')"));

        $this->assertStringContainsString("Schedule::command('system:cloud-backup')", $scheduler);
        $this->assertStringContainsString('->everyMinute()', $scheduler);
        $this->assertStringContainsString('->withoutOverlapping()', $scheduler);
        $this->assertStringContainsString("config('services.facebook.scheduler_enabled', false)", $scheduler);

        $this->assertStringContainsString('ShouldQueue', $job);
        $this->assertStringContainsString('uploadBackup(', $job);
    }
}
