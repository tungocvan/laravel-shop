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

    public function test_inventory_pwa_is_read_only_responsive_and_uses_managed_presentation(): void
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

}
