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
    }

    public function test_client_products_workspace_consumes_pharma_catalog_without_admin_mutations(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/product-show.blade.php'));

        $this->assertStringContainsString("'route' => 'client.pharma.products'", $manifest);
        $this->assertStringContainsString('MedicineCatalog $catalog', $controller);
        $this->assertStringContainsString('$catalog->browse(', $controller);
        $this->assertStringContainsString('Danh mục thuốc · chỉ đọc', $view);
        $this->assertStringContainsString('@foreach([25, 50, 100] as $size)', $view);
        $this->assertStringContainsString('{{ $size }} / trang', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString("route('client.pharma.products.show'", $view);
        $this->assertStringNotContainsString('>SKU</th>', $view);
        $this->assertStringNotContainsString('>Nhà sản xuất</th>', $view);
        $this->assertStringContainsString('Chi tiết sản phẩm · chỉ đọc', $detail);
        $this->assertStringContainsString('Mã SKU', $detail);
        $this->assertStringContainsString('Nhà sản xuất', $detail);
        $this->assertStringNotContainsString('Medicine::query()', $controller);
        $this->assertStringNotContainsString('Admin::', $view);

        foreach (['create', 'edit', 'delete', 'destroy', 'verifyMaster', 'import'] as $mutation) {
            $this->assertStringNotContainsString($mutation, $view);
        }
    }
}
