<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOrderAuthoringCapabilityTest extends TestCase
{
    public function test_order_authoring_routes_and_permissions_are_separate_from_inventory(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringContainsString("Route::get('/orders/create'", $routes);
        $this->assertStringContainsString("Route::post('/orders'", $routes);
        $this->assertStringContainsString("Route::get('/orders/{issue}/edit'", $routes);
        $this->assertStringContainsString("Route::put('/orders/{issue}'", $routes);
        $this->assertStringContainsString("Route::post('/orders/{issue}/submit'", $routes);
        $this->assertStringContainsString('client.feature:pharma,orders,create', $routes);
        $this->assertStringContainsString('client.feature:pharma,orders,submit', $routes);
        $this->assertStringContainsString("'permission' => 'client.pharma.orders.create'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.orders.submit'", $manifest);
        $this->assertStringNotContainsString('client.pharma.inventory.issues', $routes);
    }

    public function test_canonical_service_owns_scope_price_and_draft_mutations(): void
    {
        $root = base_path();
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderAuthoringService.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');

        $this->assertStringContainsString('final class UserOrderAuthoringService', $service);
        $this->assertStringContainsString("->activeAt(\$date)", $service);
        $this->assertStringContainsString("->orWhereHas('globalUsers'", $service);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::STATUS_ACTIVE', $service);
        $this->assertStringContainsString("'unit_price' => (float) \$item->company_sale_price", $service);
        $this->assertStringContainsString("'unit_price' => \$row->unit_price", $service);
        $this->assertStringContainsString('InventoryIssue::PENDING_APPROVAL', $service);
        $this->assertStringContainsString("'submitted_by' => \$userId", $service);
        $this->assertStringContainsString('guardEditable($userId, $issue)', $service);
        $this->assertStringContainsString('UserOrderAuthoringService $authoring', $controller);
        $this->assertStringNotContainsString('PriceList::query()', $controller);
        $this->assertStringNotContainsString('DrugBidAwardAllocation::query()', $controller);
    }

    public function test_order_form_is_mobile_first_and_browser_cannot_authoritative_price(): void
    {
        $root = base_path();
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php');
        $list = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('Thêm mới đơn hàng', $view);
        $this->assertStringContainsString('Theo bảng giá', $view);
        $this->assertStringContainsString('Theo kết quả trúng thầu', $view);
        $this->assertStringContainsString('Tìm khách hàng...', $view);
        $this->assertStringContainsString('Sản phẩm theo bảng giá', $view);
        $this->assertStringContainsString('Sản phẩm trúng thầu được phân công', $view);
        $this->assertStringContainsString('Lưu nháp', $view);
        $this->assertStringContainsString("el.disabled=isBid", $view);
        $this->assertStringContainsString("el.disabled=!isBid", $view);
        $this->assertStringNotContainsString('name="unit_price', $view);
        $this->assertStringNotContainsString('name="price', $view);
        $this->assertStringContainsString('data-create-order', $list);
        $this->assertStringContainsString('Chờ duyệt', $list);
        $this->assertStringContainsString('Sửa đơn', $detail);
        $this->assertStringContainsString('Gửi duyệt', $detail);
    }

    public function test_order_authoring_migration_preserves_stock_posting_boundary(): void
    {
        $root = base_path();
        $migration = file_get_contents($root.'/Modules/Pharma/database/migrations/2026_09_30_120000_add_order_authoring_to_inventory_issues.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderAuthoringService.php');

        $this->assertStringContainsString("'recipient_partner_id'", $migration);
        $this->assertStringContainsString("'submitted_by'", $migration);
        $this->assertStringContainsString("'submitted_at'", $migration);
        $this->assertStringNotContainsString('posted_by', $migration);
        $this->assertStringNotContainsString('postIssue(', $service);
        $this->assertStringNotContainsString('InventoryTransaction', $service);
        $this->assertStringNotContainsString('quantity_on_hand', $service);
    }
}
