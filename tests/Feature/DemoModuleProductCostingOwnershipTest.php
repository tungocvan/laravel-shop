<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DemoModuleProductCostingOwnershipTest extends TestCase
{
    public function test_module_owns_product_costing_route_and_blade(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.product-costing');
        $this->assertNotNull($route);
        $this->assertSame(
            'Modules\\Demo\\Http\\Controllers\\Projects\\ProductCosting\\ProductCostingController',
            $route->getActionName()
        );
        $this->get('/demo/tinh-gia-thanh-san-pham')->assertOk()
            ->assertSee('Tính giá thành sản phẩm')
            ->assertSee('data-demo-menu="tinh-gia-thanh-san-pham"', false)
            ->assertSee('demo-assets/tinh-gia-thanh-san-pham/shared/menu-editor.js', false)
            ->assertSee('demo-assets/tinh-gia-thanh-san-pham/js/integrations.js', false);
    }

    public function test_module_assets_are_allowlisted(): void
    {
        foreach (['css/app.css','js/app.js','js/integrations.js','shared/menu-editor.css','shared/menu-editor.js'] as $asset) {
            $this->get('/demo-assets/tinh-gia-thanh-san-pham/'.$asset)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/tinh-gia-thanh-san-pham/.env')->assertNotFound();
    }

    public function test_datasets_and_five_theme_choices_are_preserved(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/tinh-gia-thanh-san-pham/datasets')->assertOk();
        $js = file_get_contents(base_path('Modules/Demo/resources/assets/projects/product-costing/js/integrations.js'));
        foreach (['original:', 'ruby:', 'ocean:', 'emerald:', 'indigo:', 'product_costing_theme_v1'] as $marker) {
            $this->assertStringContainsString($marker, $js);
        }
    }
}
