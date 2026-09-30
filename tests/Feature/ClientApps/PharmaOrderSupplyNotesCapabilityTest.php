<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOrderSupplyNotesCapabilityTest extends TestCase
{
    public function test_approver_can_save_shortage_notes_without_reserving_or_posting_stock(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderSupplyNoteService.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString("Route::post('/orders/{issue}/supply-notes'", $routes);
        $this->assertStringContainsString('saveOrderSupplyNotes', $controller);
        $this->assertStringContainsString("client.pharma.orders.approve", $controller);
        $this->assertStringContainsString('final class UserOrderSupplyNoteService', $service);
        $this->assertStringContainsString('InventoryIssueDeferredSupply::updateOrCreate', $service);
        $this->assertStringContainsString("'quantity' => (float) \$row['shortage_quantity']", $service);
        $this->assertStringContainsString("'created_by' => \$actorUserId", $service);
        $this->assertStringNotContainsString('InventoryTransaction', $service);
        $this->assertStringNotContainsString('postIssue(', $service);
        $this->assertStringNotContainsString('lockForUpdate', $service);

        $this->assertStringContainsString('Hiện kho đang hết/thiếu hàng', $view);
        $this->assertStringContainsString('Dự kiến cung cấp lại', $view);
        $this->assertStringContainsString('Ghi chú *', $view);
        $this->assertStringContainsString('Lưu ghi chú', $view);
        $this->assertStringContainsString('Hiện kho đang hết hàng. Đơn hàng dự kiến cung cấp lại.', $view);
        $this->assertStringContainsString("now()->addMonth()->format('Y-m-d')", $view);
        $this->assertStringContainsString("{{ \$savedSupply ? 'Cập nhật ghi chú' : 'Lưu ghi chú' }}", $view);
        $this->assertStringNotContainsString('data-copy-from', $view);
        $this->assertStringContainsString('sản phẩm thiếu hàng phải có ghi chú chờ cung cấp', $view);
        $this->assertStringContainsString("'can_approve'", file_get_contents($root.'/Modules/Pharma/Services/UserOrderStockReadinessService.php'));
        $this->assertStringContainsString('ít nhất 1 sản phẩm đủ tồn', $view);
    }
}
