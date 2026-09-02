<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\PricingExportController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistExportController;
use Modules\Muasamcong\Http\Controllers\SyncedPricingScopedExportController;
use Tests\TestCase;

class MuasamcongRefactorArchitectureContractTest extends TestCase
{
    public function test_export_routes_use_scope_adapters_without_changing_canonical_uris(): void
    {
        $pricingExport = Route::getRoutes()->getByName('muasamcong.pricing.export-selected');
        $syncedExport = Route::getRoutes()->getByName('muasamcong.synced.export-selected');
        $wishlistExport = Route::getRoutes()->getByName('muasamcong.wishlist.export-selected');
        $wishlist = Route::getRoutes()->getByName('muasamcong.wishlist');

        $this->assertNotNull($pricingExport);
        $this->assertNotNull($syncedExport);
        $this->assertNotNull($wishlistExport);
        $this->assertNotNull($wishlist);

        $this->assertSame('admin/muasamcong/pricing/export-selected', $pricingExport->uri());
        $this->assertSame(PricingExportController::class, $pricingExport->getActionName());
        $this->assertSame('admin/muasamcong/synced/export-selected', $syncedExport->uri());
        $this->assertSame(SyncedPricingScopedExportController::class, $syncedExport->getActionName());
        $this->assertSame('admin/muasamcong/wishlist/export-selected', $wishlistExport->uri());
        $this->assertSame(PricingWishlistExportController::class, $wishlistExport->getActionName());
        $this->assertSame(PricingWishlistController::class, $wishlist->getActionName());
    }

    public function test_wishlist_delete_has_capability_specific_route_authorization(): void
    {
        $route = Route::getRoutes()->getByName('muasamcong.wishlist.destroy-selected');

        $this->assertNotNull($route);
        $this->assertContains('permission:view_muasamcong,admin', $route->gatherMiddleware());
        $this->assertContains('permission:muasamcong.pricing.wishlist,admin', $route->gatherMiddleware());
    }

    public function test_export_scope_adapters_allow_empty_selection_and_enforce_capability_permissions(): void
    {
        $pricing = file_get_contents(base_path('Modules/Muasamcong/Http/Controllers/PricingExportController.php'));
        $synced = file_get_contents(base_path('Modules/Muasamcong/Http/Controllers/SyncedPricingScopedExportController.php'));
        $wishlist = file_get_contents(base_path('Modules/Muasamcong/Http/Controllers/PricingWishlistExportController.php'));

        $this->assertIsString($pricing);
        $this->assertIsString($synced);
        $this->assertIsString($wishlist);

        $this->assertStringContainsString("'selected_ids' => ['nullable', 'array'", $pricing);
        $this->assertStringContainsString("'selected_ids' => ['nullable', 'array'", $synced);
        $this->assertStringContainsString("'selected_ids' => ['nullable', 'array'", $wishlist);
        $this->assertStringContainsString("'muasamcong.pricing.sync'", $pricing);
        $this->assertStringContainsString("'muasamcong.pricing.sync'", $synced);
        $this->assertStringContainsString("'muasamcong.pricing.wishlist'", $wishlist);
    }

    public function test_wishlist_uses_bounded_page_sizes_filter_aware_export_and_explicit_admin_pagination(): void
    {
        $controller = file_get_contents(base_path('Modules/Muasamcong/Http/Controllers/PricingWishlistController.php'));
        $view = file_get_contents(base_path('Modules/Muasamcong/resources/views/wishlist.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Muasamcong/resources/views/vendor/pagination/admin-muasamcong.blade.php'));

        $this->assertIsString($controller);
        $this->assertIsString($view);
        $this->assertIsString($pagination);

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $controller);
        $this->assertStringContainsString('private const DEFAULT_PER_PAGE = 25;', $controller);
        $this->assertStringContainsString('name="q" value="{{ $keyword }}"', $view);
        $this->assertStringContainsString('Xuất Excel — tất cả phù hợp', $view);
        $this->assertStringContainsString("links('Muasamcong::vendor.pagination.admin-muasamcong')", $view);
        $this->assertStringContainsString('border-indigo-600 bg-indigo-600', $pagination);
        $this->assertStringContainsString('border-gray-300 bg-white', $pagination);
    }

    public function test_module_contract_exists_and_records_refactor_invariants(): void
    {
        $contract = file_get_contents(base_path('docs/modules/Muasamcong/MODULE.md'));

        $this->assertIsString($contract);
        $this->assertStringContainsString('Type: `domain`', $contract);
        $this->assertStringContainsString('ClientPortal → Muasamcong', $contract);
        $this->assertStringContainsString('checkbox export semantics', $contract);
        $this->assertStringContainsString('Persistence is a protected boundary', $contract);
    }
}
