<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Invoices\Services\GoogleDriveInvoiceModuleBackupService;
use Modules\Invoices\Services\InvoiceModuleSnapshotService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Tests\Concerns\CreatesInvoicesRestoreSchema;
use Tests\TestCase;

class InvoicesGoogleDriveModuleBackupTest extends TestCase
{
    use CreatesInvoicesRestoreSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoicesRestoreSchema();
        Storage::fake('local');
    }

    public function test_disconnected_drive_reports_not_connected_without_remote_call(): void
    {
        $drive = $this->mock(GoogleDriveConnectionService::class);
        $drive->shouldReceive('status')->once()->andReturn(['connected' => false]);

        Http::fake();

        $service = app(GoogleDriveInvoiceModuleBackupService::class);

        $this->assertFalse($service->isConnected());
        Http::assertNothingSent();
    }

    public function test_snapshot_archive_contract_contains_only_module_restore_payload(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $snapshot = app(InvoiceModuleSnapshotService::class)->create('manual');
        $drive = $this->mock(GoogleDriveConnectionService::class);
        $drive->shouldReceive('testConnection')->once()->andReturn(['folder_id' => 'root-folder']);
        $drive->shouldReceive('accessToken')->once()->andReturn('token');

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::sequence()
                ->push(['files' => [['id' => 'invoices-folder', 'name' => 'Invoices']]], 200)
                ->push(['files' => [['id' => 'backup-folder', 'name' => 'Module-Backups']]], 200)
                ->push(['files' => []], 200)
                ->push(['id' => 'drive-file'], 200),
            'https://www.googleapis.com/upload/drive/v3/files/*' => Http::response(['id' => 'drive-file'], 200),
        ]);

        $result = app(GoogleDriveInvoiceModuleBackupService::class)->uploadSnapshot($snapshot['directory']);

        $this->assertSame('drive-file', $result['id']);
        $this->assertSame('Laravel-Backup/Invoices/Module-Backups', $result['folder']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $result['checksum']);
        $this->assertFalse($result['already_exists']);
    }

    public function test_drive_listing_ignores_non_module_backup_files(): void
    {
        $drive = $this->mock(GoogleDriveConnectionService::class);
        $drive->shouldReceive('status')->once()->andReturn(['connected' => true]);
        $drive->shouldReceive('testConnection')->once()->andReturn(['folder_id' => 'root-folder']);
        $drive->shouldReceive('accessToken')->once()->andReturn('token');

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::sequence()
                ->push(['files' => [['id' => 'invoices-folder']]], 200)
                ->push(['files' => [['id' => 'backup-folder']]], 200)
                ->push(['files' => [
                    ['id' => 'good', 'name' => 'Invoices-Module-20260908_160000.zip', 'size' => '1000', 'modifiedTime' => '2026-09-08T09:00:00Z', 'appProperties' => ['sha256' => str_repeat('a', 64)]],
                    ['id' => 'bad', 'name' => 'other.zip', 'size' => '1000'],
                ]], 200),
        ]);

        $files = app(GoogleDriveInvoiceModuleBackupService::class)->files();

        $this->assertCount(1, $files);
        $this->assertSame('good', $files[0]['id']);
    }

    public function test_drive_listing_falls_back_to_module_contract_when_expected_folder_is_empty(): void
    {
        $drive = $this->mock(GoogleDriveConnectionService::class);
        $drive->shouldReceive('status')->once()->andReturn(['connected' => true]);
        $drive->shouldReceive('testConnection')->once()->andReturn(['folder_id' => 'root-folder']);
        $drive->shouldReceive('accessToken')->once()->andReturn('token');

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::sequence()
                ->push(['files' => [['id' => 'invoices-folder']]], 200)
                ->push(['files' => [['id' => 'backup-folder']]], 200)
                ->push(['files' => []], 200)
                ->push(['files' => [[
                    'id' => 'fallback-file',
                    'name' => 'Invoices-Module-20260908_170000.zip',
                    'size' => '2048',
                    'modifiedTime' => '2026-09-08T10:00:00Z',
                    'parents' => ['legacy-backup-folder'],
                    'appProperties' => ['module' => 'Invoices', 'sha256' => str_repeat('b', 64)],
                ]]], 200),
        ]);

        $files = app(GoogleDriveInvoiceModuleBackupService::class)->files();

        $this->assertCount(1, $files);
        $this->assertSame('fallback-file', $files[0]['id']);
        $this->assertSame(str_repeat('b', 64), $files[0]['checksum']);
    }
}
