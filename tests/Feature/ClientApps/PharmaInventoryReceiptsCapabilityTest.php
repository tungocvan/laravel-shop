<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryReceiptsCapabilityTest extends TestCase
{
    public function test_receipt_pwa_is_read_only_and_permission_scoped(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryReceiptWorkspace.php');

        $this->assertStringContainsString("Route::get('/inventory/receipts'", $routes);
        $this->assertStringContainsString("Route::get('/inventory/receipts/{receipt}'", $routes);
        $this->assertStringNotContainsString("Route::post('/inventory/receipts", $routes);
        $this->assertStringNotContainsString("Route::put('/inventory/receipts", $routes);
        $this->assertStringNotContainsString("Route::delete('/inventory/receipts", $routes);
        $this->assertStringContainsString("'permission' => 'client.pharma.inventory.receipts'", $manifest);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.inventory.receipts')", $controller);
        $this->assertStringContainsString('UserInventoryReceiptWorkspace $workspace', $controller);
        $this->assertStringContainsString('InventoryReceipt::query()', $workspace);
        $this->assertStringNotContainsString('InventoryReceipt::query()', $controller);
        $this->assertStringContainsString("where('warehouse_id', \$warehouse->id)", $workspace);
        $this->assertStringContainsString("with('items.medicine')", $workspace);
    }

    public function test_receipt_list_and_detail_follow_pwa_responsive_contract(): void
    {
        $root = base_path();
        $list = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipts.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-show.blade.php');
        $inventory = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php');

        $this->assertStringContainsString('PWA chỉ đọc', $list);
        $this->assertStringContainsString('data-pwa-debounced-search', $list);
        $this->assertStringContainsString('data-pwa-search-input', $list);
        $this->assertStringContainsString('data-pwa-search-clear-button', $list);
        $this->assertStringContainsString('xl:hidden', $list);
        $this->assertStringContainsString('xl:block', $list);
        $this->assertStringContainsString('data-pwa-load-more', $list);
        $this->assertStringContainsString('Xem thêm', $list);
        $this->assertStringContainsString('Receipt detail · Read only', $detail);
        $this->assertStringContainsString('xl:hidden', $detail);
        $this->assertStringContainsString('xl:block', $detail);
        $this->assertStringContainsString('Số lô', $detail);
        $this->assertStringContainsString('Hạn dùng', $detail);
        $this->assertStringContainsString('Giá nhập', $detail);
        $this->assertStringNotContainsString('<form', $detail);
        $this->assertStringContainsString('client.pharma.inventory.receipts', $inventory);
        $this->assertStringContainsString('Chi tiết lô hàng · Chỉ đọc', $inventory);
    }
}
