<?php

namespace Tests\Feature;

use Tests\TestCase;

class StandaloneDebtManagementDemoTest extends TestCase
{
    public function test_demo_uses_single_named_get_route_and_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.debt-management');
        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('demo/quan-ly-cong-no', $route->uri());
        $this->assertSame(\App\Http\Controllers\Demo\DebtManagementController::class, $route->getControllerClass());
    }

    public function test_demo_renders_mount_and_separate_assets(): void
    {
        $this->get('/demo/quan-ly-cong-no')->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('id="app-data"', false)
            ->assertSee('/demo-assets/quan-ly-cong-no/css/app.css', false)
            ->assertSee('/demo-assets/quan-ly-cong-no/js/core.js', false)
            ->assertSee('/demo-assets/quan-ly-cong-no/js/app.js', false);
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-cong-no'));
        foreach (['css/app.css', 'js/core.js', 'js/app.js'] as $file) {
            $this->assertFileExists(public_path('demo-assets/quan-ly-cong-no/'.$file));
        }
    }
}
