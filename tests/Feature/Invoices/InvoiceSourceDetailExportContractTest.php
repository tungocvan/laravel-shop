<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceSourceDetailExportContractTest extends TestCase
{
    #[Test]
    public function source_data_export_supports_all_purchase_and_sold_scopes(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));

        $this->assertStringContainsString('public bool $exportAll = true;', $component);
        $this->assertStringContainsString('public bool $exportPurchase = false;', $component);
        $this->assertStringContainsString('public bool $exportSold = false;', $component);
        $this->assertStringContainsString('public function exportSourceDetail(InvoiceSourceDetailExportService $exporter)', $component);
        $this->assertStringContainsString("return ['purchase', 'sold'];", $component);
        $this->assertStringContainsString('wire:model.live="exportAll"', $shell);
        $this->assertStringContainsString('wire:model.live="exportPurchase"', $shell);
        $this->assertStringContainsString('wire:model.live="exportSold"', $shell);
        $this->assertStringContainsString('wire:click="exportSourceDetail"', $shell);
        $this->assertStringContainsString('Xuất Excel đầy đủ', $shell);
    }

    #[Test]
    public function export_repeats_invoice_columns_for_every_canonical_detail_line(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoiceSourceDetailExportService.php'));

        $this->assertStringContainsString("\$source->detail_payload['hdhhdvu']", $service);
        $this->assertStringContainsString('->flatMap(', $service);
        $this->assertStringContainsString("'Số hóa đơn' => \$invoice?->invoice_number", $service);
        $this->assertStringContainsString("'Ngày lập' => \$invoice?->issued_date?->format('d/m/Y')", $service);
        $this->assertStringContainsString("'Mã số thuế đối tác' => \$invoice?->tax_code", $service);
        $this->assertStringContainsString("'Tiền trước VAT' => \$invoice?->amount_before_vat", $service);
        $this->assertStringContainsString("'Tổng thanh toán' => \$invoice?->total_amount", $service);
        $this->assertStringContainsString("'Dòng chi tiết' => \$detail === null ? null : \$lineNumber", $service);
        $this->assertStringContainsString("\$row['Chi tiết - '.\$key]", $service);
        $this->assertStringContainsString('Arr::dot($detail)', $service);
    }
}
