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
        $this->assertStringContainsString("'stockReadiness' => (".'$canApproveOrder || $canPostOrder'.") ? ".'$stockReadiness->forIssue($visibleIssue) : null', $controller);

        $this->assertStringContainsString('Kiểm tra khả năng xuất kho', $view);
        $this->assertStringContainsString('chạm để ẩn/hiện', $view);
        $this->assertStringContainsString('<details class="rounded-3xl border', $view);
        $this->assertStringContainsString('Tồn khả dụng', $view);
        $this->assertStringContainsString("'has_stocked_item'", $service);
        $this->assertStringContainsString("'can_approve' => \$hasStockedItem && \$allRowsCovered", $service);
        $this->assertStringContainsString("\$fulfillableRows = \$rows->reject(fn (array \$row): bool => \$row['has_supply_note']);", $service);
        $this->assertStringContainsString("\$fulfillableRows->isNotEmpty()", $service);
        $this->assertStringContainsString("\$fulfillableRows->every(fn (array \$row): bool", $service);
        $this->assertStringContainsString("'has_complete_supply_note'", $service);
        $this->assertStringContainsString('SL đơn hàng', $view);
        $this->assertStringContainsString('Đủ hàng', $view);
        $this->assertStringContainsString('Không đủ hàng', $view);
        $this->assertStringContainsString('Lô khả dụng', $view);
        $this->assertStringContainsString('Chọn lô thực xuất và kiểm tra đủ tồn ở bước xử lý kho/Ghi sổ', $view);
        $this->assertStringNotContainsString('name="batches', $view);
    }
    public function test_direct_post_requires_every_non_deferred_item_to_fit_one_available_lot(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/UserOrderStockReadinessService.php'));
        $inventory = file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));

        $this->assertStringContainsString("$fulfillableRows = $rows->reject(fn (array $row): bool => $row['has_supply_note']);", $service);
        $this->assertStringContainsString("$canPostDirectly = $fulfillableRows->isNotEmpty()", $service);
        $this->assertStringContainsString("$fulfillableRows->every(fn (array $row): bool => collect($row['lots'])->contains(", $service);
        $this->assertStringContainsString("fn (array $lot): bool => (float) $lot['quantity_on_hand'] + 0.00005 >= (float) $row['requested_quantity']", $service);
        $this->assertStringContainsString("where('quantity_on_hand', '>=', (float) $item->quantity)", $inventory);
        $this->assertStringContainsString("->lockForUpdate()", $inventory);
        $this->assertStringContainsString('Tồn kho đã thay đổi hoặc mặt hàng thực xuất chưa có một lô đủ số lượng.', $inventory);
    }


}
