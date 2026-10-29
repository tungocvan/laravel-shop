<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StandaloneSalesDemoRouteTest extends TestCase
{
    public function test_demo_uses_one_named_get_route_and_controller(): void
    {
        $route = Route::getRoutes()->getByName('demo.sales-employees');

        $this->assertNotNull($route);
        $this->assertSame('demo/doanh-so-nhan-vien', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame(\Modules\Demo\Http\Controllers\Projects\SalesEmployees\SalesEmployeesController::class, $route->getControllerClass());
    }

    public function test_demo_renders_blade_sections_and_browser_storage_assets(): void
    {
        $response = $this->get('/demo/doanh-so-nhan-vien');

        $response->assertOk();
        $response->assertSee('<title>Tổng Hợp Doanh Số Theo Nhân Viên</title>', false);
        foreach (['dashboard', 'ban', 'tonghop', 'thang', 'sanpham', 'nv', 'sp', 'guide', 'settings'] as $page) {
            $response->assertSee('id="page-'.$page.'"', false);
        }
        $response->assertSee('demo-assets/doanh-so-nhan-vien/css/app.css', false);
        $response->assertSee('demo-assets/doanh-so-nhan-vien/js/app.js', false);
        $response->assertSee('id="du-lieu"', false);

        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/sales-employees/css/app.css'));
        $js = file_get_contents(base_path('Modules/Demo/resources/assets/projects/sales-employees/js/app.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('localStorage', $js);
        $this->assertStringNotContainsString('showSaveFilePicker', $js);
    }
}
