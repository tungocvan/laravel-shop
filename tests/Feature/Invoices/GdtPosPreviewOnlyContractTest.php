<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtPosPreviewOnlyContractTest extends TestCase
{
    #[Test]
    public function pos_backfill_preview_exports_without_persisting_or_fetching_details(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));
        $start = strpos($service, 'public function exportCashRegisterPreview(');
        $end = strpos($service, 'private function fetchInvoicesByMonth(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $preview = substr($service, $start, $end - $start);
        $this->assertStringContainsString('fetchInvoicesByMonth($token, $cursor, $chunkEnd, $show, $vatIn, true)', $preview);
        $this->assertStringContainsString('exportExcel($all, $vatIn, $filename)', $preview);
        $this->assertStringNotContainsString('persistInvoices(', $preview);
        $this->assertStringNotContainsString('acquireMissingDetails(', $preview);
    }

    #[Test]
    public function hoadon_ui_uses_canonical_pos_sync_and_retains_legacy_import_tool(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $this->assertStringContainsString("public string \$invoiceSource = 'all'", $component);
        $this->assertStringContainsString("'in:all,regular,pos'", $component);
        $this->assertStringNotContainsString('exportCashRegisterPreview(', $component);
        $this->assertStringContainsString('wire:model.live="invoiceSource"', $view);
        $this->assertStringContainsString('Chỉ hóa đơn máy tính tiền', $view);
        $this->assertStringContainsString('importSelectedFile', $view);
    }
}
