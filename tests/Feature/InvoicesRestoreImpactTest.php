<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Services\InvoiceRestoreImpactService;
use Tests\Concerns\CreatesInvoicesRestoreSchema;
use Tests\TestCase;

class InvoicesRestoreImpactTest extends TestCase
{
    use CreatesInvoicesRestoreSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoicesRestoreSchema();
    }

    public function test_preview_never_reports_partner_master_changes(): void
    {
        $preview = app(InvoiceRestoreImpactService::class)->preview([]);

        $this->assertSame(0, $preview['partner_master_changes']);
        $this->assertSame(0, $preview['invoices']['delete']);
        $this->assertSame('merge', $preview['recommended_mode']);
    }

    public function test_preview_detects_new_existing_and_different_invoices(): void
    {
        DB::table('invoices')->insert([
            'symbol' => 'C26TAA',
            'invoice_number' => '100',
            'tax_code' => '0312345678',
            'issued_date' => '2026-09-01',
            'invoice_type' => 'purchase',
            'name' => 'Current name',
        ]);

        $preview = app(InvoiceRestoreImpactService::class)->preview([
            [
                'symbol' => 'C26TAA',
                'invoice_number' => '100',
                'tax_code' => '0312345678',
                'issued_date' => '2026-09-01',
                'invoice_type' => 'purchase',
                'name' => 'Snapshot name',
            ],
            [
                'symbol' => 'C26TAA',
                'invoice_number' => '101',
                'tax_code' => '0312345678',
                'issued_date' => '2026-09-01',
                'invoice_type' => 'purchase',
            ],
        ]);

        $this->assertSame(1, $preview['invoices']['insert']);
        $this->assertSame(1, $preview['invoices']['existing']);
        $this->assertSame(1, $preview['invoices']['different']);
        $this->assertSame(0, $preview['invoices']['delete']);
        $this->assertSame(0, $preview['partner_master_changes']);
    }
}
