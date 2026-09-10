<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoldInvoiceMonthlyGoodsClassificationContractTest extends TestCase
{
    #[Test]
    public function sold_invoice_month_can_be_bulk_classified_as_goods_and_overridden_later(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));

        $this->assertStringContainsString('public function applySoldMonthAsGoods(): void', $component);
        $this->assertStringContainsString("\$this->invoiceType !== 'sold'", $component);
        $this->assertStringContainsString("->where('invoice_type', 'sold')", $component);
        $this->assertStringContainsString("->whereYear('issued_date', (int) \$this->year)", $component);
        $this->assertStringContainsString("->whereMonth('issued_date', (int) \$this->month)", $component);
        $this->assertStringContainsString("'business_classification' => 'GOODS'", $component);
        $this->assertStringContainsString("'classification_scope' => 'INVOICE'", $component);
        $this->assertStringContainsString("'expense_category_id' => null", $component);
        $this->assertStringContainsString('Bạn vẫn có thể đổi lại từng hóa đơn khi cần.', $component);

        $this->assertStringContainsString("@if (\$invoiceType === 'sold')", $shell);
        $this->assertStringContainsString('wire:click="applySoldMonthAsGoods"', $shell);
        $this->assertStringContainsString('Áp dụng tháng này là Hàng hóa', $shell);
        $this->assertStringContainsString("@disabled(\$year === 'all' || \$month === 'all')", $shell);
        $this->assertStringContainsString('Các phân loại hiện có trong tháng này cũng sẽ được chuyển thành Hàng hóa', $shell);
    }
}
