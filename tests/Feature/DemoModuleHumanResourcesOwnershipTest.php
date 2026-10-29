<?php

namespace Tests\Feature;

use Tests\TestCase;

final class DemoModuleHumanResourcesOwnershipTest extends TestCase
{
    public function test_hr_routes_are_owned_by_demo_module(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.human-resources');
        $this->assertNotNull($route);
        $this->assertSame('Modules\\Demo\\Http\\Controllers\\Projects\\HumanResources\\HumanResourcesController', $route->getActionName());
        $this->get('/demo/quan-ly-nhan-su')->assertOk()
            ->assertSee('data-demo-menu="quan-ly-nhan-su"', false)
            ->assertSee('data-hr-theme-choice="original"', false)
            ->assertSee('id="savedData"', false);
    }

    public function test_hr_assets_are_served_from_allowlisted_module_paths(): void
    {
        foreach (['css/app.css','js/app.js','js/menu-adapter.js','shared/menu-editor.css','shared/menu-editor.js'] as $asset) {
            $this->get('/demo-assets/quan-ly-nhan-su/'.$asset)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/quan-ly-nhan-su/private.env')->assertNotFound();
    }

    public function test_original_business_js_and_hr_menu_bridge_are_preserved(): void
    {
        $js = file_get_contents(base_path('Modules/Demo/resources/assets/projects/human-resources/js/app.js'));
        $bridge = file_get_contents(base_path('Modules/Demo/resources/assets/projects/human-resources/js/menu-adapter.js'));
        foreach (['qlns_data_v2','qlns_theme_v1','original:{primary:', 'hrStoreLibrary','hrRestore'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringContainsString('window.HrDemoMenuBridge', $bridge);
        $this->assertStringContainsString('nav.append(system)', $bridge);
    }
}
