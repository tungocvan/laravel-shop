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
        $this->assertSame(\App\Http\Controllers\Demo\SalesEmployeesController::class, $route->getControllerClass());
    }

    public function test_demo_renders_blade_sections_and_browser_storage_assets(): void
    {
        $response = $this->get('/demo/doanh-so-nhan-vien');

        $response->assertOk();
        $response->assertSee('<title>Tổng Hợp Doanh Số Theo Nhân Viên</title>', false);
        foreach (['dashboard', 'ban', 'tonghop', 'thang', 'sanpham', 'nv', 'sp', 'guide', 'settings'] as $page) {
            $response->assertSee('id="page-'.$page.'"', false);
        }
        $response->assertSee('demo/sales-employees/css/app.css', false);
        $response->assertSee('demo/sales-employees/js/app.js', false);
        $response->assertSee('id="du-lieu"', false);

        $this->assertFileExists(public_path('demo/sales-employees/css/app.css'));
        $js = file_get_contents(public_path('demo/sales-employees/js/app.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('localStorage', $js);
        $this->assertStringNotContainsString('showSaveFilePicker', $js);
    }
}
