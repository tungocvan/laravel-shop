<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\SitemapController;
use Tests\TestCase;

class WebsiteProductionOptimizationTest extends TestCase
{
    public function test_sitemap_route_and_response_contract_are_registered(): void
    {
        $route = Route::getRoutes()->getByName('sitemap');

        $this->assertNotNull($route);
        $this->assertSame('sitemap.xml', $route->uri());
        $this->assertSame(SitemapController::class, $route->getActionName());

        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/SitemapController.php'));
        $this->assertStringContainsString("'Content-Type' => 'application/xml; charset=UTF-8'", $controller);
        $this->assertStringContainsString("'Cache-Control' => 'public, max-age=3600'", $controller);
        $this->assertStringContainsString('->limit(45000)', $controller);
    }

    public function test_storefront_cache_has_targeted_invalidation(): void
    {
        $header = file_get_contents(base_path('Modules/Website/Services/HeaderMenuService.php'));
        $homepage = file_get_contents(base_path('Modules/Website/Services/HomepageContentService.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringNotContainsString('Cache::flush()', $header);
        $this->assertStringContainsString('website.homepage.composition', $homepage);
        $this->assertStringContainsString('HomepageContentService::clearCache()', $provider);
        $this->assertStringContainsString("Cache::forget('website.sitemap.xml')", $provider);
    }

    public function test_storefront_performance_indexes_are_declared(): void
    {
        $migration = file_get_contents(base_path('Modules/Website/database/migrations/2026_08_11_180000_add_storefront_performance_indexes.php'));

        foreach ([
            'wp_products_active_created_index',
            'wp_products_active_sold_index',
            'wp_posts_status_published_index',
            'wp_banners_position_active_order_index',
            'reviews_product_approved_index',
        ] as $index) {
            $this->assertStringContainsString($index, $migration);
        }
    }

    public function test_frontend_not_found_uses_a_real_404_response(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString("response()->view('Website::errors.404', [], 404)", $bootstrap);
        $this->assertStringContainsString("redirect()->guest(route('login'))", $bootstrap);
        $this->assertFileExists(base_path('Modules/Website/resources/views/errors/404.blade.php'));
    }
}
