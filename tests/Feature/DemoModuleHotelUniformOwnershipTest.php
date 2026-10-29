<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformDatasetController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformAssetController;
use Tests\TestCase;

final class DemoModuleHotelUniformOwnershipTest extends TestCase
{
    public function test_ui_and_dataset_routes_belong_to_demo_module(): void
    {
        $route = Route::getRoutes()->getByName('demo.hotel-uniform');
        $this->assertNotNull($route);
        $this->assertSame('demo/quan-ly-dong-phuc-khach-san', $route->uri());
        $this->assertSame(HotelUniformController::class, $route->getActionName());

        foreach (['demo.hotel-uniform.datasets', 'demo.hotel-uniform.datasets.show', 'demo.hotel-uniform.datasets.store'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringStartsWith(HotelUniformDatasetController::class.'@', $route->getActionName());
        }
    }

    public function test_module_view_and_allowlisted_assets(): void
    {
        $this->assertTrue(view()->exists('demo::projects.hotel-uniform.index'));
        $this->assertSame(HotelUniformAssetController::class, Route::getRoutes()->getByName('demo.hotel-uniform.asset')->getActionName());
        $this->assertFileDoesNotExist(public_path('demo-assets/quan-ly-dong-phuc-khach-san/js/app.js'));
        $this->get('/demo-assets/quan-ly-dong-phuc-khach-san/js/app.js')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/demo-assets/quan-ly-dong-phuc-khach-san/shared/menu-editor.js')->assertOk();
        $this->get('/demo-assets/quan-ly-dong-phuc-khach-san/unknown.js')->assertNotFound();
    }

    public function test_server_snapshot_endpoint_still_requires_operator_code(): void
    {
        $this->assertContains($this->get('/demo/quan-ly-dong-phuc-khach-san/datasets')->status(), [403, 503]);
    }
}
