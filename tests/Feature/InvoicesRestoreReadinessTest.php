<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceRestoreReadinessService;
use Tests\TestCase;

class InvoicesRestoreReadinessTest extends TestCase
{
    public function test_invalid_manifest_blocks_restore(): void
    {
        $result = app(InvoiceRestoreReadinessService::class)->inspect([
            'manifest_valid' => false,
            'checksum_valid' => true,
            'version_supported' => true,
        ]);

        $this->assertSame(InvoiceRestoreReadinessService::BLOCKED, $result['status']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_newer_records_require_warning_when_schema_is_ready(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('invoice_files')) {
            $this->markTestSkipped('Invoices schema is not available.');
        }

        $result = app(InvoiceRestoreReadinessService::class)->inspect([
            'manifest_valid' => true,
            'checksum_valid' => true,
            'version_supported' => true,
            'newer_current_records' => 3,
            'drive_available' => true,
        ]);

        $this->assertSame(InvoiceRestoreReadinessService::WARNING, $result['status']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_partner_master_is_not_part_of_restore_readiness_schema_gate(): void
    {
        $service = new InvoiceRestoreReadinessService;
        $reflection = new \ReflectionClass($service);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringNotContainsString("Schema::hasTable('partners')", $source);
    }
}
