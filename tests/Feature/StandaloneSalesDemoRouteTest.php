<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StandaloneSalesDemoRouteTest extends TestCase
{
    public function test_demo_route_is_registered_outside_modules(): void
    {
        $route = Route::getRoutes()->getByName('demo.sales-employees');

        $this->assertNotNull($route);
        $this->assertSame('demo/doanh-so-nhan-vien', $route->uri());
        $this->assertContains('GET', $route->methods());
    }

    public function test_demo_serves_original_html_with_browser_only_storage(): void
    {
        $path = resource_path('demo/sales-employees.html');
        $this->assertFileExists($path, 'Install the supplied demo HTML artifact before running this test.');

        $response = $this->get('/demo/doanh-so-nhan-vien');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('Tổng Hợp Doanh Số Theo Nhân Viên', false);
        $response->assertSee('localStorage', false);
        $response->assertDontSee('showSaveFilePicker', false);
    }
}
