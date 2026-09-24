<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class InventoryReceiptDraftContractTest extends TestCase
{
    public function test_draft_receipt_editor_can_change_add_and_remove_medicines(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-edit.blade.php'));

        $this->assertStringContainsString("\$medicines=\$this->medicines()", $controller);
        $this->assertStringContainsString("items.*.medicine_id", $controller);
        $this->assertStringContainsString("Không được trùng Thuốc + Số lô + Hạn dùng", $controller);
        $this->assertStringContainsString('data-medicine-select', $view);
        $this->assertStringContainsString('+ Thêm sản phẩm', $view);
        $this->assertStringContainsString('data-remove-receipt-row', $view);
        $this->assertStringContainsString("new TomSelect", $view);
        $this->assertStringContainsString("items[{{ \$i }}][medicine_id]", $view);
    }

    public function test_posted_receipt_keeps_goods_locked(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-edit.blade.php'));

        $this->assertStringContainsString("if(\$receipt->status===InventoryReceipt::POSTED)", $controller);
        $this->assertStringContainsString("@if(\$receipt->status === 'draft')", $view);
        $this->assertStringContainsString('Dữ liệu hàng hóa đã ghi sổ được giữ nguyên.', $controller);
    }
}
