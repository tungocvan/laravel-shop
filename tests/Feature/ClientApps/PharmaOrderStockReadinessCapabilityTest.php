<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOrderStockReadinessCapabilityTest extends TestCase
{
    public function test_pending_order_approval_reads_stock_without_reserving_or_posting_it(): void
    {
        $root = base_path();
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderStockReadinessService.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('final class UserOrderStockReadinessService', $service);
        $this->assertStringContainsString("where('warehouse_id', \$issue->warehouse_id)", $service);
        $this->assertStringContainsString("where('quantity_on_hand', '>', 0)", $service);
        $this->assertStringContainsString("whereDate('expiry_date', '>=', now()->toDateString())", $service);
        $this->assertStringContainsString("'available_stock'", $service);
        $this->assertStringContainsString("'shortage_quantity'", $service);
        $this->assertStringContainsString("'lots'", $service);
        $this->assertStringNotContainsString('lockForUpdate', $service);
        $this->assertStringNotContainsString('InventoryTransaction', $service);
        $this->assertStringNotContainsString('postIssue(', $service);
        $this->assertStringNotContainsString('update(', $service);
        $this->assertStringNotContainsString('create(', $service);

        $this->assertStringContainsString('UserOrderStockReadinessService $stockReadiness', $controller);
        $this->assertStringContainsString("'stockReadiness' => \$canApproveOrder ? \$stockReadiness->forIssue(\$visibleIssue) : null", $controller);

        $this->assertStringContainsString('Kiểm tra khả năng xuất kho', $view);
        $this->assertStringContainsString('Tồn khả dụng', $view);
        $this->assertStringContainsString('SL đơn hàng', $view);
        $this->assertStringContainsString('Đủ hàng', $view);
        $this->assertStringContainsString('Không đủ hàng', $view);
        $this->assertStringContainsString('Lô khả dụng', $view);
        $this->assertStringContainsString('Chọn lô thực xuất ở bước xử lý kho', $view);
        $this->assertStringNotContainsString('name="batches', $view);
    }
}
