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
        $this->assertStringContainsString("abort_if(\$filter === 'supplier-priced' && ! \$canViewSupplierPricing, 403)", $controller);
        $this->assertStringContainsString('$catalog->overview($variant, $canViewSupplierPricing)', $controller);
        $this->assertStringContainsString('Danh mục thuốc · chỉ đọc', $view);
        $this->assertStringContainsString('@foreach([25, 50, 100] as $size)', $view);
        $this->assertStringContainsString('{{ $size }} / trang', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString('number_format($products->total()', $view);
        $this->assertStringContainsString('Đã trúng thầu', $view);
        $this->assertStringContainsString('Có HSSP', $view);
        $this->assertStringContainsString('Có giá NCC', $view);
        $this->assertStringContainsString('$filterCounts[$value ?? \'all\']', $view);
        $this->assertStringContainsString('name="group"', $view);
        $this->assertStringContainsString('Tất cả nhóm', $view);
        $this->assertStringContainsString('@foreach($circularGroups as $group)', $view);
        $this->assertStringContainsString("'group' => \$circularGroup", $view);
        $this->assertStringContainsString('>Nhóm</th>', $view);
        $this->assertStringContainsString('>Giá kê khai</th>', $view);
        $this->assertStringContainsString('$product->circularGroup', $view);
        $this->assertStringContainsString('$product->declaredPrice', $view);
        $this->assertStringContainsString('h-[46px]', $view);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_11rem_8rem_auto]', $view);
        $this->assertStringContainsString('whitespace-nowrap', $view);
        $this->assertStringContainsString('bg-blue-50', $view);
        $this->assertStringContainsString('bg-emerald-50', $view);
        $this->assertStringContainsString('bg-amber-50', $view);
        $this->assertStringContainsString('$product->hasBidAward', $view);
        $this->assertStringContainsString('$product->hasProfile', $view);
        $this->assertStringContainsString('$canViewSupplierPricing && $product->hasSupplierPricing', $view);
        $this->assertStringContainsString('if ($canViewSupplierPricing)', $view);
        $this->assertStringContainsString("\$filters['supplier-priced'] = ['label' => 'Có giá NCC'", $view);
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
        $this->assertStringNotContainsString('Medicine::query()', $controller);
        $this->assertStringNotContainsString('Admin::', $view);

        foreach (['create', 'edit', 'delete', 'destroy', 'verifyMaster', 'import'] as $mutation) {
            $this->assertStringNotContainsString($mutation, $view);
        }
    }
}
