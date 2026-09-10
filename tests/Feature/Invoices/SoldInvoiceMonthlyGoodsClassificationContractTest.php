<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoldInvoiceMonthlyGoodsClassificationContractTest extends TestCase
{
    #[Test]
    public function sold_invoice_month_only_bulk_classifies_unclassified_rows_and_hides_action_when_complete(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));

        $this->assertStringContainsString('public function applySoldMonthAsGoods(): void', $component);
        $this->assertStringContainsString("\$this->invoiceType !== 'sold'", $component);
        $this->assertStringContainsString("->where('invoice_type', 'sold')", $component);
        $this->assertStringContainsString("->where('business_classification', 'UNCLASSIFIED')", $component);
        $this->assertStringContainsString("->whereYear('issued_date', (int) \$this->year)", $component);
        $this->assertStringContainsString("->whereMonth('issued_date', (int) \$this->month)", $component);
        $this->assertStringContainsString("'business_classification' => 'GOODS'", $component);
        $this->assertStringContainsString("'classification_scope' => 'INVOICE'", $component);
        $this->assertStringContainsString('Các hóa đơn đã phân loại trước đó được giữ nguyên.', $component);

        $this->assertStringContainsString("@if (\$invoiceType === 'sold' && \$stats['unclassified'] > 0)", $shell);
        $this->assertStringContainsString('wire:click="applySoldMonthAsGoods"', $shell);
        $this->assertStringContainsString('Áp dụng tháng này là Hàng hóa', $shell);
        $this->assertStringContainsString("@disabled(\$year === 'all' || \$month === 'all')", $shell);
        $this->assertStringContainsString('Chỉ các hóa đơn đang Chưa phân loại được chuyển thành Hàng hóa', $shell);
    }
}
