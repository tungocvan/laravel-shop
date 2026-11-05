<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\MedicineCatalog;
use ReflectionClass;
use Tests\TestCase;

class PharmaProductsCapabilityTest extends TestCase
{
    public function test_pharma_products_route_is_read_only_and_feature_guarded(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $route = Route::getRoutes()->getByName('client.pharma.products');

        $this->assertNotNull($route);
        $this->assertSame('GET', $route->methods()[0]);
        $this->assertSame('apps/pharma/products', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:pharma', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,products', $route->gatherMiddleware());
        $this->assertNotContains('auth:admin', $route->gatherMiddleware());

        $showRoute = Route::getRoutes()->getByName('client.pharma.products.show');
        $this->assertNotNull($showRoute);
        $this->assertSame('GET', $showRoute->methods()[0]);
        $this->assertSame('apps/pharma/products/{variant}', $showRoute->uri());
        $this->assertContains('client.feature:pharma,products', $showRoute->gatherMiddleware());
    }

    public function test_medicine_catalog_exposes_paginated_browse_as_canonical_read_contract(): void
    {
        $catalog = new ReflectionClass(MedicineCatalog::class);

        $this->assertTrue($catalog->hasMethod('browse'));
        $this->assertTrue($catalog->getMethod('browse')->isPublic());

        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php'));

        $this->assertStringContainsString('public function browse(', $service);
        $this->assertStringContainsString('paginate($perPage', $service);
        $this->assertStringContainsString('[25, 50, 100]', $service);
        $this->assertStringContainsString('MedicineCatalogItem::fromVariant', $service);
        $dto = file_get_contents(base_path('Modules/Pharma/DTOs/MedicineCatalogItem.php'));
        $this->assertStringContainsString('public ?string $circularGroup', $dto);
        $this->assertStringContainsString('public ?float $declaredPrice', $dto);
        $this->assertStringContainsString('public bool $hasBidAward = false', $dto);
        $this->assertStringContainsString('public bool $hasProfile = false', $dto);
        $this->assertStringContainsString('public bool $hasSupplierPricing = false', $dto);
        $this->assertStringContainsString("\$filter === 'awarded'", $service);
        $this->assertStringContainsString("\$filter === 'profile'", $service);
        $this->assertStringContainsString("\$filter === 'supplier-priced' && \$allowSupplierPricing", $service);
        $this->assertStringContainsString("whereNotNull('import_price')", $service);
        $this->assertStringContainsString('public function circularGroups(): Collection', $service);
        $this->assertStringContainsString("where('circular_group', \$circularGroup)", $service);
        $this->assertStringContainsString('public function filterCounts(bool $includeSupplierPricing = false): array', $service);
        $this->assertStringContainsString("orderBy('catalog_medicines.circular_group')", $service);
        $this->assertStringContainsString("orderBy('catalog_medicines.circular_order_number')", $service);
        $this->assertStringContainsString("orderBy('catalog_medicines.name')", $service);
        $this->assertStringContainsString('public function overview(int $variantId, bool $includeSupplierPricing = false): ?array', $service);
        $this->assertStringContainsString('if ($includeSupplierPricing)', $service);
        $this->assertStringContainsString("\$row['import_price']", $service);
    }

    public function test_client_products_workspace_consumes_pharma_catalog_without_admin_mutations(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/product-show.blade.php'));

        $this->assertStringContainsString("'route' => 'client.pharma.products'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.products.supplier-pricing'", $manifest);
        $this->assertStringContainsString('MedicineCatalog $catalog', $controller);
        $this->assertStringContainsString('$catalog->browse(', $controller);
        $this->assertStringContainsString('$catalog->filterCounts($canViewSupplierPricing)', $controller);
        $this->assertStringContainsString("'group' => ['nullable', 'string', 'max:100']", $controller);
        $this->assertStringContainsString('$catalog->circularGroups()', $controller);
        $this->assertStringContainsString('circularGroup: $circularGroup', $controller);
        $this->assertStringContainsString("client.pharma.products.supplier-pricing", $controller);
        $this->assertStringContainsString("abort_if((\$filter === 'supplier-priced' || filled(\$validated['supplier_price'] ?? null)) && ! \$canViewSupplierPricing, 403)", $controller);
        $this->assertStringContainsString('$catalog->overview($variant, $canViewSupplierPricing)', $controller);
        // ClientPortal catalog now supports permission-gated Medicine Master CRUD.
        $this->assertStringContainsString("route('client.pharma.products.create')", $view);
        $this->assertStringContainsString("@can('client.pharma.products.create')", $view);
        $this->assertStringNotContainsString('name="per_page"', $view);
        // SKU results use PWA load-more; master-only medicines have their own paginator.
        $this->assertStringNotContainsString('$products->links()', $view);
        $this->assertStringContainsString('$masterOnlyMedicines->links()', $view);
        $this->assertStringContainsString('data-pwa-load-more-targets', $view);
        $this->assertStringContainsString('Xem thêm', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString('number_format($products->total()', $view);
        $this->assertStringContainsString('Đã trúng thầu', $view);
        $this->assertStringContainsString('Có HSSP', $view);
        $this->assertStringContainsString('Có giá NCC', $view);
        $this->assertStringContainsString('name="bid_award"', $view);
        $this->assertStringContainsString('name="group"', $view);
        $this->assertStringContainsString('name="hssp"', $view);
        $this->assertStringContainsString('Chưa có HSSP', $view);
        $this->assertStringContainsString('Chưa có giá NCC', $view);
        $this->assertStringContainsString('Chưa trúng thầu', $view);
        $this->assertStringNotContainsString('aria-label="Bộ lọc danh mục thuốc"', $view);
        $this->assertStringContainsString('Tất cả nhóm', $view);
        $this->assertStringContainsString('@foreach($circularGroups as $group)', $view);
        $this->assertStringContainsString('name="group"', $view);
        $this->assertStringContainsString('>Nhóm</th>', $view);
        $this->assertStringContainsString('>Giá kê khai</th>', $view);
        $this->assertStringContainsString('$product->circularGroup', $view);
        $this->assertStringContainsString('$product->declaredPrice', $view);
        $this->assertStringContainsString('h-[46px]', $view);
        $this->assertStringContainsString('sm:grid-cols-2 xl:grid-cols-4', $view);
        $this->assertStringContainsString('whitespace-nowrap', $view);
        $this->assertStringContainsString('bg-blue-50', $view);
        $this->assertStringContainsString('bg-emerald-50', $view);
        $this->assertStringContainsString('bg-amber-50', $view);
        $this->assertStringContainsString('$product->hasBidAward', $view);
        $this->assertStringContainsString('$product->hasProfile', $view);
        $this->assertStringContainsString('$canViewSupplierPricing && $product->hasSupplierPricing', $view);
        $this->assertStringContainsString('@if($canViewSupplierPricing)', $view);
        $this->assertStringContainsString('name="supplier_price"', $view);
        $this->assertStringContainsString('data-pwa-debounced-search="350"', $view);
        $this->assertStringContainsString('data-pwa-search-region="#product-results-region"', $view);
        $this->assertStringContainsString('data-pwa-search-clear="#product-search-clear"', $view);
        $this->assertStringContainsString('data-pwa-search-clear-button="#product-search-input"', $view);
        $this->assertStringContainsString('id="product-results-region"', $view);
        $this->assertStringNotContainsString("window.setTimeout(() => form.requestSubmit(), 350)", $view);
        $this->assertStringNotContainsString('>Tìm kiếm</button>', $view);
        $this->assertStringContainsString("route('client.pharma.products.show'", $view);
        $this->assertStringNotContainsString('>SKU</th>', $view);
        $this->assertStringNotContainsString('>Nhà sản xuất</th>', $view);
        $this->assertStringContainsString('Thông tin sản phẩm', $detail);
        $this->assertStringContainsString("['Hoạt chất', \$product->activeIngredients]", $detail);
        $this->assertStringContainsString('Nhà sản xuất', $detail);
        $this->assertStringContainsString('Hồ sơ sản phẩm', $detail);
        $this->assertStringContainsString('Thông tin trúng thầu gần đây', $detail);
        $this->assertStringContainsString('Thông tin nhà cung cấp', $detail);
        $this->assertStringContainsString('@if($supplierPricingVisible)', $detail);
        $this->assertStringContainsString('Giá thu NCC', $detail);
        $this->assertStringContainsString('$awards->isNotEmpty()', $detail);
        $this->assertStringContainsString('$supplierPricingVisible && $suppliers->contains', $detail);
        $this->assertStringContainsString('@if($supplierPricingVisible)', $detail);
        $this->assertStringNotContainsString('Giá thương mại được bảo vệ theo quyền riêng.', $detail);
        // The SKU workspace uses MedicineCatalog; a separate scoped query lists
        // Medicine Master records that have no SKU yet.
        $this->assertStringContainsString('$catalog->browse(', $controller);
        $this->assertStringContainsString('\\Modules\\Pharma\\Models\\Medicine::query()', $controller);
        $this->assertStringContainsString("->whereDoesntHave('variants')", $controller);
        $this->assertStringNotContainsString('Admin::', $view);

        // CRUD actions are allowed only behind explicit ClientPortal capabilities.
        foreach (['create', 'edit', 'delete'] as $capability) {
            $this->assertStringContainsString("@can('client.pharma.products.{$capability}')", $view);
        }
        $this->assertStringContainsString("route('client.pharma.products.create')", $view);
        $this->assertStringContainsString("route('client.pharma.products.edit'", $view);
        $this->assertStringContainsString("route('client.pharma.products.destroy'", $view);
        $this->assertStringContainsString('@csrf', $view);
        $this->assertStringContainsString("@method('DELETE')", $view);
        $this->assertStringContainsString('onsubmit="return confirm(', $view);
        $this->assertStringNotContainsString('method="DELETE"', $view);
        $this->assertStringNotContainsString("route('client.pharma.products.verifyMaster'", $view);
    }

    public function test_catalog_product_type_and_special_control_filters_use_pharma_owned_query(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString('Medicine::productTypeOptions()', $service);
        $this->assertStringContainsString("whereDoesntHave('medicine.currentProfile')", $service);
        $this->assertStringContainsString("whereDoesntHave('medicine.drugBidAwards')", $service);
        $this->assertStringContainsString("whereDoesntHave('medicine.supplierTrackings'", $service);
        $this->assertStringContainsString("supplierPrice: \$validated['supplier_price'] ?? null", $controller);
        $this->assertStringContainsString("bidAward: \$validated['bid_award'] ?? null", $controller);
        $this->assertStringContainsString("hssp: \$validated['hssp'] ?? null", $controller);
        $this->assertStringContainsString("where('product_type', \$productType)", $service);
        $this->assertStringContainsString("where('is_special_control', \$specialControl === 'yes')", $service);
        $this->assertStringContainsString("productType: \$validated['product_type'] ?? null", $controller);
        $this->assertStringContainsString("specialControl: \$validated['special_control'] ?? null", $controller);
        $this->assertStringContainsString('name="product_type"', $view);
        $this->assertStringContainsString('name="special_control"', $view);
        $this->assertStringContainsString('@selected($selectedProductType', $view);
        $this->assertStringContainsString('@selected($selectedSpecialControl', $view);
    }

    public function test_catalog_selection_uses_medicine_ids_and_supports_loaded_mobile_and_desktop_rows(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString('data-medicine-select-all', $view);
        $this->assertStringContainsString('data-medicine-select value="{{ $product->medicineId }}"', $view);
        $this->assertStringContainsString('data-medicine-selected-count', $view);
        $this->assertStringContainsString('new Set(', $view);
        $this->assertStringContainsString("querySelectorAll('[data-medicine-select]')", $view);
        $this->assertStringContainsString('MutationObserver(sync)', $view);
        $this->assertStringContainsString('selected.size', $view);
        $this->assertStringContainsString('selectAll.indeterminate', $view);
        $this->assertStringContainsString('clear.hidden = selected.size === 0', $view);
        $this->assertStringNotContainsString('x-data=', $view);
        $this->assertStringContainsString('data-pwa-load-more-targets', $view);
        $this->assertStringNotContainsString('value="{{ $product->variantId }}"', $view);
    }

    public function test_client_medicine_excel_export_is_permission_gated_and_preserves_pwa_context(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString("name('products.export')", $routes);
        $this->assertStringContainsString('client.pharma.products.export', $manifest);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.products.export')", $controller);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.products.supplier-pricing')", $controller);
        $this->assertStringContainsString("'ids.*' => ['required', 'integer', 'min:1', 'distinct']", $controller);
        $this->assertStringContainsString('$catalog->browse(', $controller);
        $this->assertStringContainsString('while ($result->hasMorePages())', $controller);
        $this->assertStringContainsString('$configured->export($ids, $profile, $canPrice)', $controller);
        $this->assertStringContainsString("name('products.import')", $routes);
        $this->assertStringContainsString('client.pharma.products.import', $manifest);
        $this->assertStringContainsString('MedicineCatalogUploadService $upload', $controller);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.products.import')", $controller);
        $this->assertStringContainsString("['required', 'file', 'mimes:xlsx,csv', 'max:10240']", $controller);
        $this->assertStringContainsString("['source' => 'clientportal_pharma']", $controller);
        $this->assertStringNotContainsString('->commit(', $controller);
        // Import was moved to a dedicated page; the catalog only links to it.
        $importView = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import.blade.php'));
        $this->assertStringContainsString("route('client.pharma.products.import.page')", $view);
        $this->assertStringContainsString('name="file"', $importView);
        $this->assertStringContainsString('id="medicine-export-form"', $view);
        $this->assertStringContainsString("data.append('ids[]', id)", $view);
        $this->assertStringContainsString("credentials: 'same-origin'", $view);
        $this->assertStringContainsString("cache: 'no-store'", $view);
        $this->assertStringContainsString('navigator.canShare(shareData)', $view);
        $this->assertStringContainsString('await navigator.share({ files: [preparedFile] })', $view);
    }

    public function test_detailed_excel_contains_profile_award_supplier_and_special_control_fields(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineImportExport.php'));
        $related = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelRelatedDataService.php'));

        foreach (['exportDetailedCatalog', 'Mã thuốc', 'SKU', 'Hoạt chất kiểm soát đặc biệt',
            'Hồ sơ sản phẩm (HSSP)', 'Trúng thầu', 'Công ty trúng thầu',
            'Nơi trúng thầu', 'Số quyết định trúng thầu', 'Giá trúng thầu',
            'Số lượng phân bổ', 'Nhà cung cấp', 'Giá vốn NCC', 'Giá nhập NCC'] as $field) {
            $this->assertStringContainsString($field, $service);
        }
        $this->assertStringContainsString('if ($includeSupplierPricing)', $service);
        $this->assertStringContainsString('public function preview(?Medicine $medicine): array', $related);
    }

    public function test_client_excel_configuration_uses_web_scoped_profiles_and_shared_writer(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelProfileController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));
        $writer = file_get_contents(base_path('Modules/Pharma/Services/MedicineConfiguredExcelExporter.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString("name('products.excel-config')", $routes);
        $this->assertStringContainsString('client.pharma.products.excel-config', $manifest);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.products.excel-config')", $controller);
        $this->assertStringContainsString("'web'", $controller);
        $this->assertStringContainsString("where('owner_guard', \$guard)", $service);
        $this->assertStringContainsString("string('owner_guard', 16)->default('admin')", file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_04_000001_scope_medicine_excel_profiles_by_guard.php')));
        $this->assertStringContainsString("unset(\$allowed['cost_price'], \$allowed['supplier_name'])", $writer);
        $this->assertStringContainsString('name="profile_id"', $view);
        $this->assertStringContainsString("route('client.pharma.products.excel-config')", $view);
    }

    public function test_clientportal_uses_the_original_pharma_excel_designer_v4_with_locked_guard(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/excel-config.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $adminView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        $this->assertStringContainsString("@livewire('pharma.medicine.excel-configurator'", $view);
        $this->assertStringContainsString("'ownerGuard' => 'web'", $view);
        $this->assertStringContainsString("'openOnMount' => true", $view);
        $this->assertStringContainsString('@livewireStyles', $view);
        $this->assertStringContainsString('@livewireScripts', $view);
        $this->assertStringContainsString('#[Locked]', $component);
        $this->assertStringContainsString('private function assertOwner(): int', $component);
        $this->assertStringContainsString('client.pharma.products.excel-config', $component);
        $this->assertStringContainsString('client.pharma.products.supplier-pricing', $component);
        $this->assertStringContainsString('$this->ownerGuard', $component);
        foreach (['Thương hiệu', 'Cột dữ liệu', 'Trang in', 'Nhân đôi', 'Tạo mới', 'Lưu cấu hình'] as $label) {
            $this->assertStringContainsString($label, $adminView);
        }
    }

    public function test_excel_json_import_and_centered_wrapped_headers(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelProfileController.php'));
        $writer = file_get_contents(base_path('Modules/Pharma/Services/MedicineConfiguredExcelExporter.php'));
        $this->assertStringContainsString("name('products.excel-config.import-json')", $routes);
        $this->assertStringContainsString('function importJson(', $controller);
        $this->assertStringContainsString('->setWrapText(true)', $writer);
        $this->assertStringContainsString('Alignment::HORIZONTAL_CENTER', $writer);
        $this->assertStringContainsString('Alignment::VERTICAL_CENTER', $writer);
    }
}
