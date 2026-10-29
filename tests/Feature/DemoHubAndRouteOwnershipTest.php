<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DemoHubAndRouteOwnershipTest extends TestCase
{
    private const ROUTES = [
        'demo.rental-room-management',
        'demo.hotel-uniform',
        'demo.equipment-maintenance',
        'demo.coffee-shop-sales',
        'demo.daily-work-report',
        'demo.enterprise-management-report',
        'demo.attendance-payroll',
        'demo.human-resources',
        'demo.debt-management',
        'demo.sales-employees',
        'demo.warehouse-inventory',
        'demo.product-costing',
    ];

    public function test_hub_links_to_twelve_distinct_module_owned_demo_routes(): void
    {
        $response = $this->get('/demo')->assertOk()->assertSee('Khám phá 12 giao diện Demo');
        $this->assertCount(12, self::ROUTES);
        foreach (self::ROUTES as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertStringStartsWith('Modules\\Demo\\', $route->getActionName(), $name);
            $response->assertSee('href="'.route($name).'"', false);
        }
    }

    public function test_shared_menu_api_is_module_owned_and_preserves_independent_storage(): void
    {
        Storage::fake('local');
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/demo/quan-ly-xuat-nhap-ton/menu', 'GET')
        );
        $this->assertSame(
            'Modules\\Demo\\Http\\Controllers\\Shared\\SharedSidebarMenuController@show',
            $route->getActionName()
        );
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/menu')->assertOk()->assertJsonStructure(['config']);
        $this->getJson('/demo/doanh-so-nhan-vien/menu')->assertOk()->assertJsonStructure(['config']);
    }

    public function test_app_routes_do_not_register_demo_endpoints(): void
    {
        $contents = file_get_contents(base_path('routes/web.php'));
        $this->assertStringNotContainsString("Route::prefix('/demo/", $contents);
        $this->assertStringNotContainsString("Route::get('/demo", $contents);
        $this->assertStringContainsString('Route::fallback', $contents);
    }
}
