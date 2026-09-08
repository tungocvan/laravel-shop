<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Invoices\Services\InvoiceModuleSnapshotService;
use Tests\Concerns\CreatesInvoicesRestoreSchema;
use Tests\TestCase;

class InvoicesModuleSnapshotTest extends TestCase
{
    use CreatesInvoicesRestoreSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoicesRestoreSchema();
    }

    public function test_snapshot_excludes_partner_master_and_pdf_binaries(): void
    {
        Storage::fake('local');
        $snapshot = app(InvoiceModuleSnapshotService::class)->create('test');

        $this->assertFalse($snapshot['manifest']['partner_master_included']);
        $this->assertFalse($snapshot['manifest']['pdf_binaries_included']);
        $this->assertSame(DB::table('invoices')->count(), $snapshot['manifest']['tables']['invoices']);
        $this->assertSame(DB::table('invoice_files')->count(), $snapshot['manifest']['tables']['invoice_files']);

        $inspection = app(InvoiceModuleSnapshotService::class)->inspect($snapshot['directory']);
        $this->assertTrue($inspection['manifest_valid']);
        $this->assertTrue($inspection['checksum_valid']);
        $this->assertTrue($inspection['version_supported']);
    }

    public function test_modified_snapshot_payload_fails_checksum(): void
    {
        Storage::fake('local');
        $service = app(InvoiceModuleSnapshotService::class);
        $snapshot = $service->create('test');
        Storage::disk('local')->put($snapshot['directory'].'/database/invoices.json', 'tampered');

        $inspection = $service->inspect($snapshot['directory']);
        $this->assertFalse($inspection['checksum_valid']);
    }
}
