<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DemoModuleWarehouseInventoryOwnershipTest extends TestCase
{
    public function test_warehouse_route_and_all_business_sections_render_from_module(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.warehouse-inventory');
        $this->assertNotNull($route);
        $this->assertSame(
            'Modules\\Demo\\Http\\Controllers\\Projects\\WarehouseInventory\\WarehouseInventoryController',
            $route->getActionName()
        );
        $this->get('/demo/quan-ly-xuat-nhap-ton')->assertOk()
            ->assertSee('id="page-settings"', false)
            ->assertSee('data-demo-menu="quan-ly-xuat-nhap-ton"', false)
            ->assertSee('demo-assets/quan-ly-xuat-nhap-ton/shared/menu-editor.js', false);
    }

    public function test_warehouse_assets_are_allowlisted(): void
    {
        foreach (['css/app.css','js/app.js','shared/menu-editor.css','shared/menu-editor.js'] as $asset) {
            $this->get('/demo-assets/quan-ly-xuat-nhap-ton/'.$asset)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/quan-ly-xuat-nhap-ton/.env')->assertNotFound();
    }

    public function test_dataset_and_five_themes_remain_available(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets')->assertOk();
        $js = file_get_contents(base_path('Modules/Demo/resources/assets/projects/warehouse-inventory/js/app.js'));
        foreach (['original:', 'indigo:', 'ruby:', 'ocean:', 'emerald:', 'warehouse_inventory_theme_v1', 'localStorage'] as $marker) {
            $this->assertStringContainsString($marker, $js);
        }
    }
}
