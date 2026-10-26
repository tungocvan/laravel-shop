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

    public function test_warehouse_settings_offer_four_persisted_themes(): void
    {
        $response = $this->get('/demo/quan-ly-xuat-nhap-ton');
        $response->assertOk()->assertSee('id="warehouseThemeSettings"', false);
        foreach (['indigo', 'ruby', 'ocean', 'emerald'] as $theme) {
            $response->assertSee('data-warehouse-theme-choice="'.$theme.'"', false);
        }

        $js = file_get_contents(public_path('demo/warehouse-inventory/js/app.js'));
        $css = file_get_contents(public_path('demo/warehouse-inventory/css/app.css'));
        $layout = file_get_contents(resource_path('views/demo/warehouse-inventory/layouts/app.blade.php'));
        $this->assertStringContainsString('warehouse_inventory_theme_v1', $js);
        $this->assertStringContainsString('localStorage.setItem(WAREHOUSE_THEME_KEY,theme)', $js);
        $this->assertStringContainsString('applyWarehouseTheme(WAREHOUSE_SAVED_THEME)', $js);
        $this->assertStringContainsString('data-warehouse-theme="ocean"', $css);
        $this->assertStringContainsString('data-warehouse-theme="emerald"', $css);
        $this->assertStringContainsString('data-warehouse-theme="ruby"', $css);
        $this->assertStringContainsString('@media(max-width:420px)', $css);
        $this->assertStringContainsString("filemtime(public_path('demo/warehouse-inventory/css/app.css'))", $layout);
        $this->assertStringContainsString("filemtime(public_path('demo/warehouse-inventory/js/app.js'))", $layout);
    }
}
