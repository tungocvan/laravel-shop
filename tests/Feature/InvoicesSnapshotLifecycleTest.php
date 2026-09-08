<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\Invoices\Services\InvoiceModuleSnapshotService;
use RuntimeException;
use Tests\Concerns\CreatesInvoicesRestoreSchema;
use Tests\TestCase;

class InvoicesSnapshotLifecycleTest extends TestCase
{
    use CreatesInvoicesRestoreSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoicesRestoreSchema();
        Storage::fake('local');
    }

    public function test_manual_snapshot_can_be_deleted(): void
    {
        $service = app(InvoiceModuleSnapshotService::class);
        $snapshot = $service->create('manual');

        $service->delete($snapshot['directory']);

        Storage::disk('local')->assertMissing($snapshot['directory'].'/manifest.json');
    }

    public function test_safety_snapshot_is_protected_from_normal_delete(): void
    {
        $service = app(InvoiceModuleSnapshotService::class);
        $snapshot = $service->create('safety-before-restore');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Safety Backup đang được bảo vệ');

        $service->delete($snapshot['directory']);
    }

    public function test_snapshot_delete_rejects_paths_outside_invoice_backup_root(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Đường dẫn snapshot Invoices không hợp lệ');

        app(InvoiceModuleSnapshotService::class)->delete('../danger');
    }
}
