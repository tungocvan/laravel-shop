<?php

namespace Tests\Feature\Product;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductRouteConfigurationTest extends TestCase
{
    public function test_product_admin_routes_keep_expected_names_and_permission_middleware(): void
    {
        $this->assertRouteHasMiddleware('admin.products.index', 'permission:view_product,admin');
        $this->assertRouteHasMiddleware('admin.products.create', 'permission:create_product,admin');
        $this->assertRouteHasMiddleware('admin.products.edit', 'permission:edit_product,admin');
        $this->assertRouteHasMiddleware('admin.products.commissions', 'permission:edit_product,admin');
    }

    public function test_product_api_route_is_not_registered_without_contract(): void
    {
        $this->assertFalse(Route::has('api.product.index'));
    }

    private function assertRouteHasMiddleware(string $name, string $middleware): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] was not registered.");
        $this->assertContains($middleware, $route->gatherMiddleware());
    }
}
