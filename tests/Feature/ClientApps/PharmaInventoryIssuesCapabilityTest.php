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
        $this->assertStringContainsString('findVisible((int) $user->id, $issue, $canApproveOrder)', $controller);
        $this->assertStringContainsString('findVisible(int $userId, int $issueId, bool $includeApprovalScope = false)', $workspace);
        $this->assertStringContainsString('managerUserId: $managerUserId', $controller);
        $this->assertStringContainsString("managerOptions((int) \$user->id, true)", $controller);
        $this->assertStringContainsString("if (\$includeApprovalScope) {", $workspace);
        $this->assertStringContainsString("return \$query;", $workspace);
        $this->assertStringNotContainsString("orWhereIn('status', [InventoryIssue::PENDING_APPROVAL, InventoryIssue::APPROVED, InventoryIssue::REJECTED])", $workspace);
        $this->assertStringContainsString("->when(\$managerUserId, fn (Builder \$query) => \$query->where('manager_user_id', \$managerUserId))", $workspace);
        $this->assertStringContainsString('abort_if($visibleIssue === null, 404)', $controller);
        $this->assertStringNotContainsString('InventoryIssue::query()', $controller);
        $this->assertSame(2, substr_count($manifest, "'orders' => ["), 'Orders must exist once in navigation and once in features.');
        $this->assertSame(2, substr_count($manifest, "'route' => 'client.pharma.orders'"), 'Orders route must be registered in navigation and features.');
        $this->assertStringContainsString("'description' => 'Lập và theo dõi đơn hàng theo bảng giá hoặc kết quả trúng thầu trong phạm vi User.'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.orders'", $manifest);
        $this->assertStringNotContainsString("'route' => 'client.pharma.inventory.issues'", $manifest);
        $this->assertStringNotContainsString('Đơn hàng / Phiếu xuất', $inventoryView);
    }

    public function test_inventory_issue_list_keeps_mobile_native_reference_with_authoring_entry(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');

        $this->assertStringContainsString("Route::post('/orders'", $routes);
        $this->assertStringContainsString("Route::put('/orders/{issue}'", $routes);
        $this->assertStringContainsString("Route::delete('/orders/{issue}'", $routes);
        $this->assertStringContainsString('Đơn hàng', $view);
        $this->assertStringContainsString("route('client.pharma.dashboard')", $view);
        $this->assertStringNotContainsString("route('client.pharma.inventory')", $view);
        $this->assertStringContainsString('Tìm đơn hàng / khách hàng / bệnh viện', $view);
        $this->assertStringContainsString('issue-filter-sheet', $view);
        $this->assertStringContainsString('lg:inset-0 lg:m-auto lg:h-fit', $view);
        $this->assertStringContainsString('lg:max-h-[calc(100vh-3rem)]', $view);
        $this->assertStringNotContainsString('lg:right-6 lg:bottom-6', $view);
        $this->assertStringContainsString('Lọc đơn hàng', $view);
        $this->assertStringContainsString('data-order-manager-filter', $view);
        $this->assertStringContainsString('data-disabled-status', $view);
        $this->assertStringContainsString('aria-disabled="true"', $view);
        $this->assertStringContainsString('whitespace-nowrap', $view);
        $this->assertStringContainsString('data-order-status-bar', $view);
        $this->assertStringNotContainsString('id="issue-status-toggle"', $view);
        $this->assertStringNotContainsString('id="issue-status-menu"', $view);
        $this->assertStringContainsString("\$railStatuses = [''=>'Tất cả','draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã xuất','cancelled'=>'Đã hủy']", $view);
        $this->assertStringContainsString('overflow-x-auto', $view);
        $this->assertStringContainsString('w-fit shrink-0', $view);
        $this->assertStringContainsString('text-[11px]', $view);
        $this->assertStringContainsString('[&::-webkit-scrollbar]:hidden', $view);
        $this->assertStringContainsString('$statusCount > 0', $view);
        $this->assertStringContainsString('Tất cả User phụ trách', $view);
        $this->assertStringContainsString("['status','source','from_date','to_date','manager_user_id']", $view);
        $this->assertStringContainsString('Xóa lọc', $view);
        $this->assertStringContainsString('min-w-0 max-w-full overflow-x-hidden', $view);
        $this->assertStringContainsString('grid min-w-0 max-w-full grid-cols-1', $view);
        $this->assertStringContainsString('line-clamp-2 break-words', $view);
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

    public function test_inventory_issue_detail_shows_order_source_products_totals_and_draft_actions(): void
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
        $this->assertStringContainsString('Sửa đơn', $view);
        $this->assertStringContainsString('Gửi duyệt', $view);
    }

    public function test_posted_order_list_uses_actual_stock_movement_totals(): void
    {
        $root=base_path();
        $workspace=file_get_contents($root.'/Modules/Pharma/Services/UserInventoryIssueWorkspace.php');
        $view=file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');

        $this->assertStringContainsString('use Modules\\Pharma\\Models\\InventoryTransaction;', $workspace);
        $this->assertStringContainsString("where('source_type',InventoryIssue::class)", $workspace);
        $this->assertStringContainsString("where('quantity_delta','<',0)", $workspace);
        $this->assertStringContainsString("\$postedItems=\$issue->items->filter", $workspace);
        $this->assertStringContainsString("\$issue->items_count=\$postedItems->count()", $workspace);
        $this->assertStringContainsString("\$issue->total_value=(float)\$postedItems->sum", $workspace);
        $this->assertStringContainsString("\$money(\$issue->total_value ?? 0)", $view);
        $this->assertStringContainsString("'deferredSupplies.medicine:id,name,unit'", $workspace);
        $this->assertStringContainsString("\$issue->shortage_note=\$issue->deferredSupplies->map", $workspace);
        $this->assertStringContainsString('data-shortage-note', $view);
        $this->assertStringContainsString('shortage-note-dialog', $view);
        $this->assertStringContainsString('Ghi chú cung ứng', $view);
        $this->assertStringContainsString("shortageDialog.style.inset='50% auto auto 50%'", $view);
        $this->assertStringContainsString("shortageDialog.style.transform='translate(-50%, -50%)'", $view);
        $this->assertStringContainsString('max-w-[520px]', $view);
        $this->assertStringContainsString('bottom-[76px] right-4', $view);
        $this->assertStringContainsString('h-11 w-11', $view);
        $this->assertStringContainsString('lg:bottom-8 lg:right-8 lg:h-11 lg:w-auto', $view);
        $this->assertStringContainsString('<span class="hidden lg:inline">Lập đơn hàng</span>', $view);
        $this->assertStringContainsString("Dự kiến: '.\$row->expected_supply_date->format('d/m/Y')", $workspace);
        $this->assertStringContainsString('placeholder:text-[13px]', $view);
    }

}
