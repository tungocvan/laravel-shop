<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryIssuesCapabilityTest extends TestCase
{
    public function test_inventory_issue_reads_use_canonical_pharma_workspace_and_user_scope(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryIssueWorkspace.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $inventoryView = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php');

        $this->assertStringContainsString("Route::get('/orders'", $routes);
        $this->assertStringContainsString("Route::get('/orders/{issue}'", $routes);
        $this->assertStringContainsString('UserInventoryIssueWorkspace $workspace', $controller);
        $this->assertStringContainsString("client.pharma.orders", $controller);
        $this->assertStringContainsString("->where('manager_user_id', \$userId)->orWhere('created_by', \$userId)", $workspace);
        $this->assertStringContainsString('findVisible((int) $user->id, $issue)', $controller);
        $this->assertStringContainsString('abort_if($visibleIssue === null, 404)', $controller);
        $this->assertStringNotContainsString('InventoryIssue::query()', $controller);
        $this->assertStringContainsString("'orders' => [", $manifest);
        $this->assertStringContainsString("'route' => 'client.pharma.orders'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.orders'", $manifest);
        $this->assertStringNotContainsString("'route' => 'client.pharma.inventory.issues'", $manifest);
        $this->assertStringNotContainsString('Đơn hàng / Phiếu xuất', $inventoryView);
    }

    public function test_inventory_issue_list_matches_mobile_native_reference_and_remains_read_only(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');

        $this->assertStringNotContainsString("Route::post('/orders", $routes);
        $this->assertStringNotContainsString("Route::put('/orders", $routes);
        $this->assertStringNotContainsString("Route::delete('/orders", $routes);
        $this->assertStringContainsString('Đơn hàng', $view);
        $this->assertStringContainsString('Tìm đơn hàng / khách hàng / bệnh viện', $view);
        $this->assertStringContainsString('issue-filter-sheet', $view);
        $this->assertStringContainsString('lg:inset-0 lg:m-auto lg:h-fit', $view);
        $this->assertStringContainsString('lg:max-h-[calc(100vh-3rem)]', $view);
        $this->assertStringNotContainsString('lg:right-6 lg:bottom-6', $view);
        $this->assertStringContainsString('Lọc đơn hàng', $view);
        $this->assertStringContainsString('Xóa lọc', $view);
        $this->assertStringContainsString('Hủy', $view);
        $this->assertStringContainsString('Áp dụng', $view);
        $this->assertStringContainsString('Không có dữ liệu phù hợp!', $view);
        $this->assertStringContainsString('md:grid-cols-2', $view);
        $this->assertStringContainsString('xl:hidden', $view);
        $this->assertStringContainsString('xl:block', $view);
        $this->assertStringContainsString('Xem thêm', $view);
        $this->assertStringContainsString('IntersectionObserver', $view);
        $this->assertStringContainsString("window.setTimeout(() => searchForm.requestSubmit(), 350)", $view);
    }

    public function test_inventory_issue_detail_is_read_only_and_shows_order_source_products_and_totals(): void
    {
        $root = base_path();
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('Chi tiết đơn hàng', $view);
        $this->assertStringContainsString('Nguồn đơn hàng', $view);
        $this->assertStringContainsString('Theo kết quả trúng thầu', $view);
        $this->assertStringContainsString('Theo bảng giá', $view);
        $this->assertStringContainsString('Người phụ trách', $view);
        $this->assertStringContainsString('Sản phẩm', $view);
        $this->assertStringContainsString('Đơn giá', $view);
        $this->assertStringNotContainsString('<form', $view);
    }
}
