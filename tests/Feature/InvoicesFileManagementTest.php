<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\InvoiceFileManagerService;
use Modules\Invoices\Services\InvoiceFileService;
use Tests\TestCase;

class InvoicesFileManagementTest extends TestCase
{
    public function test_reconcile_tracks_existing_pdf_and_summary_counts_missing_files(): void
    {
        $this->withInvoiceFileTables(function () {
            $available = Invoices::query()->create($this->invoiceData('FM-001', 'purchase', '2026-08-10'));
            Invoices::query()->create($this->invoiceData('FM-002', 'purchase', '2026-08-11'));

            $fileService = app(InvoiceFileService::class);
            $path = $fileService->targetPdfPathForInvoice($available);
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, '%PDF-1.4 test');

            try {
                $manager = app(InvoiceFileManagerService::class);
                $result = $manager->reconcile([
                    'invoice_type' => 'purchase',
                    'issued_date_from' => '2026-08-01',
                    'issued_date_to' => '2026-08-31',
                ]);
                $summary = $manager->summary([
                    'invoice_type' => 'purchase',
                    'issued_date_from' => '2026-08-01',
                    'issued_date_to' => '2026-08-31',
                ]);

                $this->assertSame(2, $result['scanned']);
                $this->assertSame(1, $result['available']);
                $this->assertSame(1, $result['missing']);
                $this->assertSame(2, $summary['total']);
                $this->assertSame(1, $summary['available']);
                $this->assertSame(1, $summary['missing']);
                $this->assertSame(0, $summary['error']);
                $this->assertDatabaseHas('invoice_files', ['invoice_id' => $available->id, 'status' => 'available']);
            } finally {
                @unlink($path);
                $this->removeEmptyParents(dirname($path), storage_path('app/invoices/pdf'));
            }
        });
    }

    public function test_error_metadata_is_separated_from_missing_files(): void
    {
        $this->withInvoiceFileTables(function () {
            $invoice = Invoices::query()->create($this->invoiceData('FM-ERR', 'sold', '2026-08-12'));
            $manager = app(InvoiceFileManagerService::class);
            $manager->recordFailure($invoice, 'gdt', 'Test provider error');

            $summary = $manager->summary([
                'invoice_type' => 'sold',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
            ]);

            $this->assertSame(1, $summary['total']);
            $this->assertSame(0, $summary['available']);
            $this->assertSame(0, $summary['missing']);
            $this->assertSame(1, $summary['error']);
        });
    }

    public function test_delete_files_by_ids_only_removes_selected_pdf(): void
    {
        $this->withInvoiceFileTables(function () {
            $selected = Invoices::query()->create($this->invoiceData('FM-DEL-1', 'purchase', '2026-08-13'));
            $untouched = Invoices::query()->create($this->invoiceData('FM-DEL-2', 'purchase', '2026-08-13'));
            $fileService = app(InvoiceFileService::class);

            $selectedPath = $fileService->targetPdfPathForInvoice($selected);
            $untouchedPath = $fileService->targetPdfPathForInvoice($untouched);
            @mkdir(dirname($selectedPath), 0775, true);
            file_put_contents($selectedPath, '%PDF-selected');
            file_put_contents($untouchedPath, '%PDF-untouched');

            try {
                $manager = app(InvoiceFileManagerService::class);
                $manager->reconcile(['invoice_type' => 'purchase']);
                $result = $manager->deleteFilesByIds([$selected->id]);

                $this->assertSame(1, $result['deleted']);
                $this->assertSame(0, $result['failed']);
                $this->assertFileDoesNotExist($selectedPath);
                $this->assertFileExists($untouchedPath);
                $this->assertDatabaseHas('invoice_files', ['invoice_id' => $selected->id, 'status' => 'missing']);
                $this->assertDatabaseHas('invoice_files', ['invoice_id' => $untouched->id, 'status' => 'available']);
                $this->assertDatabaseHas('invoices', ['id' => $selected->id]);
                $this->assertDatabaseHas('invoices', ['id' => $untouched->id]);
            } finally {
                @unlink($selectedPath);
                @unlink($untouchedPath);
                $this->removeEmptyParents(dirname($selectedPath), storage_path('app/invoices/pdf'));
            }
        });
    }

    public function test_zip_contains_only_existing_pdfs_for_current_filter(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not installed.');
        }

        $this->withInvoiceFileTables(function () {
            $first = Invoices::query()->create($this->invoiceData('FM-ZIP-1', 'purchase', '2026-08-13'));
            Invoices::query()->create($this->invoiceData('FM-ZIP-2', 'purchase', '2026-08-14'));

            $fileService = app(InvoiceFileService::class);
            $pdfPath = $fileService->targetPdfPathForInvoice($first);
            @mkdir(dirname($pdfPath), 0775, true);
            file_put_contents($pdfPath, '%PDF-1.4 archive test');

            $manager = app(InvoiceFileManagerService::class);
            $manager->reconcile([
                'invoice_type' => 'purchase',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
            ]);

            $archive = $manager->createZip([
                'invoice_type' => 'purchase',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
            ]);

            try {
                $this->assertSame(1, $archive['count']);
                $this->assertFileExists($archive['path']);
                $zip = new \ZipArchive();
                $this->assertTrue($zip->open($archive['path']) === true);
                $this->assertSame(1, $zip->numFiles);
                $this->assertSame($fileService->filenameForInvoice($first), $zip->getNameIndex(0));
                $zip->close();
            } finally {
                @unlink($archive['path']);
                @unlink($pdfPath);
                $this->removeEmptyParents(dirname($pdfPath), storage_path('app/invoices/pdf'));
            }
        });
    }

    private function withInvoiceFileTables(callable $callback): void
    {
        Schema::dropIfExists('invoice_files');
        Schema::dropIfExists('invoices');

        $invoiceMigration = require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php');
        $invoiceMigration->up();
        $fileMigration = require base_path('Modules/Invoices/database/migrations/2026_08_15_120000_create_invoice_files_table.php');
        $fileMigration->up();

        try {
            $callback();
        } finally {
            Schema::dropIfExists('invoice_files');
            Schema::dropIfExists('invoices');
        }
    }

    private function invoiceData(string $number, string $type, string $date): array
    {
        return [
            'lookup_code' => null,
            'symbol' => '1/C26TAA',
            'invoice_number' => $number,
            'type' => 'Hóa đơn GTGT',
            'issued_date' => $date,
            'tax_code' => '0312345678',
            'name' => 'Đối tác kiểm thử',
            'tax_rate' => '10',
            'vat_amount' => '100.00',
            'amount_before_vat' => '1000.00',
            'total_amount' => '1100.00',
            'invoice_type' => $type,
        ];
    }

    private function removeEmptyParents(string $path, string $stopAt): void
    {
        $stopAt = rtrim($stopAt, DIRECTORY_SEPARATOR);
        while (str_starts_with($path, $stopAt) && $path !== $stopAt) {
            @rmdir($path);
            $path = dirname($path);
        }
    }
}
