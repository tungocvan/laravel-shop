<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesController;
use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesDatasetController;
use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesAssetController;
use Tests\TestCase;

final class DemoModuleCoffeeShopSalesOwnershipTest extends TestCase
{
    public function test_canonical_routes_are_owned_by_demo_module(): void
    {
        $route=Route::getRoutes()->getByName('demo.coffee-shop-sales');
        $this->assertNotNull($route);
        $this->assertSame('demo/quan-ly-ban-hang-ca-phe', $route->uri());
        $this->assertSame(CoffeeShopSalesController::class, $route->getActionName());
        foreach (['demo.coffee-shop-sales.datasets','demo.coffee-shop-sales.datasets.show','demo.coffee-shop-sales.datasets.store'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringStartsWith(CoffeeShopSalesDatasetController::class.'@', $route->getActionName());
        }
    }

    public function test_module_assets_and_dynamic_dom_contract(): void
    {
        $this->assertTrue(view()->exists('demo::projects.coffee-shop-sales.index'));
        $this->assertSame(CoffeeShopSalesAssetController::class, Route::getRoutes()->getByName('demo.coffee-shop-sales.asset')->getActionName());
        $this->get('/demo/quan-ly-ban-hang-ca-phe')->assertOk()
            ->assertSee('id="sb"', false)
            ->assertSee('id="du-lieu"', false)
            ->assertSee('data-demo-menu="quan-ly-ban-hang-ca-phe"', false);
        $this->get('/demo-assets/quan-ly-ban-hang-ca-phe/css/app.css')->assertOk();
        $this->get('/demo-assets/quan-ly-ban-hang-ca-phe/js/app.js')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->get('/demo-assets/quan-ly-ban-hang-ca-phe/shared/menu-editor.js')->assertOk();
        $this->get('/demo-assets/quan-ly-ban-hang-ca-phe/unknown.js')->assertNotFound();
    }
    public function test_theme_preview_cards_replace_duplicate_server_json_settings(): void
    {
        $integration=file_get_contents(base_path('Modules/Demo/resources/assets/projects/coffee-shop-sales/js/integrations.js'));
        $style=file_get_contents(base_path('Modules/Demo/resources/assets/projects/coffee-shop-sales/css/app.css'));
        $this->assertStringContainsString('coffee_shop_sales_theme_v1', $integration);
        $this->assertStringContainsString('coffee-theme-swatch', $integration);
        $this->assertStringContainsString('aria-pressed', $integration);
        foreach (['original','ruby','ocean','emerald','indigo'] as $theme) {
            $this->assertStringContainsString($theme.':', $integration);
        }
        $this->assertStringNotContainsString('coffee-dataset-upload', $integration);
        $this->assertStringNotContainsString('coffee-dataset-code', $integration);
        $this->assertStringContainsString('grid-template-columns: repeat(5', $style);
    }

}
