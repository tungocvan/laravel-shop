<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\InvoiceFileService;
use Tests\TestCase;

class InvoicesPdfStorageTest extends TestCase
{
    public function test_invoice_pdf_path_is_organized_by_year_month_and_type(): void
    {
        $this->withInvoicesTable(function () {
            $invoice = Invoices::query()->create([
                'lookup_code' => null,
                'symbol' => '1/C26T',
                'invoice_number' => '242',
                'type' => 'Hóa đơn GTGT',
                'issued_date' => '2026-08-13',
                'tax_code' => '0317953611',
                'name' => 'CÔNG TY TNHH THƯƠNG MẠI DƯỢC PHẨM KHANG PHÁT',
                'tax_rate' => '10',
                'vat_amount' => '100.00',
                'amount_before_vat' => '1000.00',
                'total_amount' => '1100.00',
                'invoice_type' => 'purchase',
            ]);

            $service = app(InvoiceFileService::class);
            $relative = $service->relativePathForInvoice($invoice);

            $this->assertStringStartsWith('invoices/pdf/2026/08/purchase/', $relative);
            $this->assertStringContainsString('2026-08-13_HD-242_0317953611_', $relative);
            $this->assertStringEndsWith('.pdf', $relative);
        });
    }

    public function test_legacy_pdf_is_still_resolved_for_existing_invoice(): void
    {
        $this->withInvoicesTable(function () {
            $invoice = Invoices::query()->create([
                'lookup_code' => 'legacy-lookup',
                'symbol' => '1/C26T',
                'invoice_number' => '89',
                'type' => 'Hóa đơn GTGT',
                'issued_date' => '2026-08-13',
                'tax_code' => '0319291096',
                'name' => 'CÔNG TY TNHH DƯỢC PHẨM SINVICO',
                'tax_rate' => '10',
                'vat_amount' => '100.00',
                'amount_before_vat' => '1000.00',
                'total_amount' => '1100.00',
                'invoice_type' => 'purchase',
            ]);

            $legacyDir = storage_path('app/hoadon_temp');
            if (! is_dir($legacyDir)) {
                mkdir($legacyDir, 0775, true);
            }

            $legacyPath = $legacyDir.'/legacy-lookup.pdf';
            file_put_contents($legacyPath, '%PDF-legacy-test');

            try {
                $service = app(InvoiceFileService::class);

                $this->assertTrue($service->existsForInvoice($invoice));
                $this->assertSame($legacyPath, $service->pdfPathForInvoice($invoice));
            } finally {
                @unlink($legacyPath);
            }
        });
    }

    private function withInvoicesTable(callable $callback): void
    {
        Schema::dropIfExists('invoices');
        $migration = require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php');
        $migration->up();

        try {
            $callback();
        } finally {
            Schema::dropIfExists('invoices');
        }
    }
}
