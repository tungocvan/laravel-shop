<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DemoModuleDebtManagementOwnershipTest extends TestCase
{
    public function test_debt_ui_route_is_module_owned_and_preserves_js_shell(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.debt-management');
        $this->assertNotNull($route);
        $this->assertSame(
            'Modules\\Demo\\Http\\Controllers\\Projects\\DebtManagement\\DebtManagementController',
            $route->getActionName()
        );
        $this->get('/demo/quan-ly-cong-no')->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('data-demo-menu="quan-ly-cong-no"', false)
            ->assertSee('demo-assets/quan-ly-cong-no/shared/menu-editor.js', false);
    }

    public function test_debt_assets_are_allowlisted_and_available(): void
    {
        foreach (['css/app.css','js/core.js','js/menu-builder.js','js/app.js','js/server-datasets.js','shared/menu-editor.css','shared/menu-editor.js'] as $asset) {
            $this->get('/demo-assets/quan-ly-cong-no/'.$asset)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/quan-ly-cong-no/.env')->assertNotFound();
    }

    public function test_debt_menu_and_dataset_routes_remain_available(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/quan-ly-cong-no/datasets')->assertOk();
        $this->getJson('/demo/quan-ly-cong-no/menu')->assertOk();
        $this->getJson('/demo/quan-ly-cong-no/menu/presets')->assertOk();
    }

    public function test_original_business_logic_themes_and_menu_bridge_are_preserved(): void
    {
        $base = base_path('Modules/Demo/resources/assets/projects/debt-management/js/');
        $business = file_get_contents($base.'app.js');
        $menu = file_get_contents($base.'menu-builder.js');
        foreach (['DEBT_THEMES','original:', 'ruby:', 'ocean:', 'emerald:', 'indigo:', 'debt_management_theme_v1'] as $marker) {
            $this->assertStringContainsString($marker, $business);
        }
        $this->assertStringContainsString('window.DebtDemoMenuBridge', $menu);
        $this->assertStringContainsString('debt_management_sidebar_menu_v1', $menu);
    }
}
