<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryCapabilityTest extends TestCase
{
    public function test_inventory_capability_is_routed_through_client_portal_and_canonical_pharma_workspace(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryWorkspace.php');

        $this->assertStringContainsString("Route::get('/inventory'", $routes);
        $this->assertStringContainsString("->name('inventory')", $routes);
        $this->assertStringContainsString("'route' => 'client.pharma.inventory'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.inventory.view'", $manifest);
        $this->assertStringContainsString('UserInventoryWorkspace $workspace', $controller);
        $this->assertStringContainsString("abort_unless(\$registry->userCan(\$user, 'client.pharma.inventory.view'), 403)", $controller);
        $this->assertStringContainsString('InventoryBalance::query()', $workspace);
        $this->assertStringContainsString('SupplierTracking::query()', $workspace);
        $this->assertStringNotContainsString('InventoryBalance::query()', $controller);
        $this->assertStringNotContainsString('SupplierTracking::query()', $controller);
    }

    public function test_inventory_pwa_is_responsive_uses_managed_presentation_and_exposes_receipt_workflow(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringContainsString("Route::post('/inventory/receipts'", $routes);
        $this->assertStringContainsString("Route::post('/inventory/receipts/{receipt}/post", $routes);
        $this->assertStringContainsString("Route::post('/inventory/receipts/{receipt}/revert", $routes);
        $this->assertStringContainsString("Route::put('/inventory/receipts/{receipt}'", $routes);
        $this->assertStringContainsString("Route::delete('/inventory/receipts/{receipt}'", $routes);
        $this->assertStringContainsString("featurePresentation['page_title']", $view);
        $this->assertStringContainsString("featurePresentation['page_description']", $view);
        $this->assertStringContainsString('xl:hidden', $view);
        $this->assertStringContainsString('xl:block', $view);
        $this->assertStringNotContainsString('{{ $size }} / trang', $view);
        $this->assertStringNotContainsString('$balances->links()', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString('inventory-search-input', $view);
        $this->assertStringContainsString('window.setTimeout(() => form.requestSubmit(), 350)', $view);
        $this->assertStringContainsString('Xóa từ khóa tìm kiếm', $view);
        $this->assertStringContainsString('md:grid-cols-2', $view);
        $this->assertStringContainsString('inventory-load-more', $view);
        $this->assertStringContainsString('Xem thêm', $view);
        $this->assertStringContainsString("fetch(more.href", $view);
        $this->assertStringContainsString('IntersectionObserver', $view);
        $this->assertStringContainsString('inventory-desktop-body', $view);
        $this->assertStringContainsString('Tên thuốc / hoạt chất', $view);
        $this->assertStringContainsString('Hoạt chất / Quy cách', $view);
        $this->assertStringNotContainsString('>Mã thuốc</th>', $view);
        $this->assertStringContainsString('border border-slate-300', $view);
        $this->assertStringContainsString('h-12 rounded-2xl', $view);
        $this->assertStringContainsString('lg:col-span-4', $view);
        $this->assertStringContainsString('lg:col-span-8', $view);
        $this->assertStringContainsString('× Xóa bộ lọc', $view);
        $this->assertStringContainsString('text-[11px] font-black text-rose-700', $view);
        $this->assertStringNotContainsString('<details id="inventory-advanced-filters"', $view);
        $this->assertStringNotContainsString('<summary', $view);
        $this->assertStringContainsString('inventory-filter-toggle', $view);
        $this->assertStringContainsString('inventory-filter-panel', $view);
        $this->assertStringContainsString("window.matchMedia('(min-width: 1024px)')", $view);
        $this->assertStringContainsString('table-fixed', $view);
        $this->assertStringContainsString('w-[27%]', $view);
        $this->assertStringContainsString('w-[13%]', $view);
        $this->assertStringContainsString('w-[15%]', $view);
        $this->assertStringContainsString('whitespace-nowrap', $view);
        $this->assertStringContainsString('Giá trị tồn theo giá vốn', $view);
        $this->assertStringContainsString('Trong đó còn hạn', $view);
        $this->assertStringContainsString('Lô chưa định giá', $view);
        $this->assertStringContainsString('Giá trị hàng cận hạn ≤ 6 tháng', $view);
        $this->assertStringContainsString('Hàng hết hạn còn tồn', $view);
        $this->assertStringContainsString('@if($canViewCosts)', $view);
        $this->assertStringContainsString("'page_title' => 'Tồn kho Pharma'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.inventory.costs'", $manifest);
        $this->assertStringContainsString("@section('hide-application-header', true)", $view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        $this->assertStringContainsString('aria-label="Quay lại Không gian làm việc Pharma"', $view);
        $this->assertStringNotContainsString('pb-24 xl:pb-8', $view);
    }

    public function test_inventory_costs_and_search_are_permission_scoped(): void
    {
        $root = base_path();
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryWorkspace.php');

        $this->assertStringContainsString("userCan(\$user, 'client.pharma.inventory.costs')", $controller);
        $this->assertStringContainsString('! $canViewCosts, 403', $controller);
        $this->assertStringContainsString("'canViewCosts' => \$canViewCosts", $controller);
        $this->assertStringContainsString('bool $canViewCosts = false', $workspace);
        $this->assertStringContainsString("->orWhere('active_ingredients', 'like'", $workspace);
        $this->assertStringNotContainsString("->where('medicine_code', 'like'", $workspace);
        $this->assertStringContainsString("if (\$canViewCosts) {", $workspace);
        $this->assertStringContainsString("\$canViewCosts ? \$this->activeSupplierCosts() : collect()", $workspace);
        $this->assertStringContainsString("'valid_count' =>", $workspace);
        $this->assertStringContainsString("'valid_value' =>", $workspace);
    }

    public function test_inventory_balance_detail_uses_canonical_ledger_and_permission_aware_pwa_navigation(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryWorkspace.php');
        $index = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-show.blade.php');

        $this->assertStringContainsString("Route::get('/inventory/balances/{balance}'", $routes);
        $this->assertStringContainsString("->name('inventory.balances.show')", $routes);
        $this->assertStringContainsString('public function inventoryBalance(', $controller);
        $this->assertStringContainsString('$workspace->detail($balance, $canViewCosts)', $controller);
        $this->assertStringContainsString('abort_if($detail === null, 404)', $controller);
        $this->assertStringContainsString('public function detail(int $balanceId, bool $canViewCosts = false): ?array', $workspace);
        $this->assertStringContainsString("->where('warehouse_id', \$warehouse->id)", $workspace);
        $this->assertStringContainsString('InventoryTransaction::query()', $workspace);
        $this->assertStringContainsString('InventoryReceipt::class', $workspace);
        $this->assertStringContainsString('InventoryIssue::class', $workspace);
        $this->assertStringNotContainsString('InventoryTransaction::query()', $controller);
        $this->assertStringContainsString("route('client.pharma.inventory.balances.show'", $index);
        $this->assertStringContainsString('Xem biến động lô', $index);
        $this->assertStringContainsString('Lịch sử biến động lô', $detail);
        $this->assertStringContainsString('Tồn đầu kỳ', $detail);
        $this->assertStringContainsString('Hoàn tác nhập', $detail);
        $this->assertStringContainsString('Hoàn tác xuất', $detail);
        $this->assertStringContainsString("@if(\$canViewCosts)", $detail);
        $this->assertStringContainsString("can('client.pharma.inventory.receipts')", $detail);
        $this->assertStringContainsString("can('client.pharma.orders')", $detail);
        $this->assertStringContainsString("@section('hide-application-header', true)", $detail);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $detail);
        $this->assertStringContainsString('← Tồn kho', $detail);
        $this->assertStringContainsString('← Quay về dashboard', $detail);
        $this->assertStringContainsString('xl:hidden', $detail);
        $this->assertStringContainsString('xl:block', $detail);
        $this->assertStringNotContainsString('method="POST"', $detail);
        $this->assertStringNotContainsString('method="DELETE"', $detail);
        $this->assertStringNotContainsString('Admin::', $detail);
    }

    public function test_inventory_documents_link_back_to_exact_canonical_balance_when_available(): void
    {
        $root = base_path();
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryWorkspace.php');
        $receipt = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-show.blade.php');
        $issue = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('public function balanceLinksForItems(iterable $items): array', $workspace);
        $this->assertStringContainsString("->where('warehouse_id', \$warehouse->id)", $workspace);
        $this->assertStringContainsString("->where('medicine_id', \$key['medicine_id'])", $workspace);
        $this->assertStringContainsString("->where('batch_number', \$key['batch_number'])", $workspace);
        $this->assertStringContainsString("->whereDate('expiry_date', \$key['expiry_date'])", $workspace);
        $this->assertStringNotContainsString('InventoryBalance::query()', $controller);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.inventory.view')", $controller);
        $this->assertStringContainsString('balanceLinksForItems($visibleReceipt->items)', $controller);
        $this->assertStringContainsString('balanceLinksForItems($visibleIssue->items)', $controller);
        $this->assertStringContainsString("'inventoryBalanceLinks' =>", $controller);
        $this->assertStringContainsString("'canViewInventory' => \$canViewInventory", $controller);
        $this->assertStringContainsString("route('client.pharma.inventory.balances.show'", $receipt);
        $this->assertStringContainsString("route('client.pharma.inventory.balances.show'", $issue);
        $this->assertStringContainsString('Xem tồn lô', $receipt);
        $this->assertStringContainsString('Xem tồn lô', $issue);
        $this->assertStringContainsString('$canViewInventory && isset($inventoryBalanceLinks[$item->id])', $receipt);
        $this->assertStringContainsString('$canViewInventory && isset($inventoryBalanceLinks[$item->id])', $issue);
    }


    public function test_receipt_detail_uses_compact_pwa_layout_without_changing_workflow_actions(): void
    {
        $root = base_path();
        $receipt = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-show.blade.php');

        $this->assertStringContainsString("@section('hide-application-header', true)", $receipt);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $receipt);
        $this->assertStringContainsString('sm:grid-cols-[9rem_minmax(0,1fr)]', $receipt);
        $this->assertStringContainsString('grid grid-cols-3 gap-2', $receipt);
        $this->assertStringContainsString('Tổng giá trị', $receipt);
        $this->assertStringContainsString('Hóa đơn chưa VAT', $receipt);
        $this->assertStringNotContainsString('Receipt detail · Workflow', $receipt);
        $this->assertStringContainsString('Gửi duyệt', $receipt);
        $this->assertStringContainsString('Hoàn tác gửi duyệt', $receipt);
        $this->assertStringContainsString('Duyệt', $receipt);
        $this->assertStringContainsString('Hoàn tác duyệt', $receipt);
        $this->assertStringContainsString('Ghi sổ', $receipt);
        $this->assertStringContainsString('Hoàn tác ghi sổ', $receipt);
        $this->assertStringContainsString('Xem tồn lô', $receipt);
        $this->assertStringContainsString("route('client.pharma.inventory.balances.show'", $receipt);
    }


}
