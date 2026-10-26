<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StandaloneWarehouseInventoryDemoTest extends TestCase
{
    public function test_warehouse_demo_has_one_named_get_route(): void
    {
        $route = Route::getRoutes()->getByName('demo.warehouse-inventory');
        $this->assertNotNull($route);
        $this->assertSame('demo/quan-ly-xuat-nhap-ton', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame(\App\Http\Controllers\Demo\WarehouseInventoryController::class, $route->getControllerClass());
    }

    public function test_warehouse_demo_renders_pages_and_assets(): void
    {
        $response = $this->get('/demo/quan-ly-xuat-nhap-ton');
        $response->assertOk();
        foreach (['dashboard', 'products', 'in', 'out', 'history', 'report', 'settings'] as $page) {
            $response->assertSee('id="page-'.$page.'"', false);
        }
        $response->assertSee('demo/warehouse-inventory/css/app.css', false);
        $response->assertSee('demo/warehouse-inventory/js/app.js', false);
        $response->assertSee('id="khoData"', false);
        $response->assertSee('id="sampleImgs"', false);
        $this->assertFileExists(public_path('demo/warehouse-inventory/css/app.css'));
        $js = file_get_contents(public_path('demo/warehouse-inventory/js/app.js'));
        $this->assertStringContainsString('localStorage', $js);
        $this->assertStringContainsString('function saveToFile()', $js);
    }
}
