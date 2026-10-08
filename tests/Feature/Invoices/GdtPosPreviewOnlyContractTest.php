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
    public function hoadon_ui_exposes_manual_pos_import_workflow(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $this->assertStringContainsString("public string \$invoiceSource = 'all'", $component);
        $this->assertStringContainsString("if (\$this->invoiceSource === 'pos')", $component);
        $this->assertStringContainsString('exportCashRegisterPreview(', $component);
        $this->assertStringContainsString('wire:model.live="invoiceSource"', $view);
        $this->assertStringContainsString('Chỉ hóa đơn máy tính tiền', $view);
        $this->assertStringContainsString('importSelectedFile', $view);
    }
}
