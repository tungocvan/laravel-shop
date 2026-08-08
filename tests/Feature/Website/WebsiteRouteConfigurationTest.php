<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\PostController;
use Tests\TestCase;

class WebsiteRouteConfigurationTest extends TestCase
{
    public function test_blog_index_route_uses_controller_action(): void
    {
        $route = Route::getRoutes()->getByName('blog.index');

        $this->assertNotNull($route, 'Route [blog.index] was not registered.');
        $this->assertSame(PostController::class . '@index', $route->getActionName());
    }

    public function test_website_admin_routes_keep_admin_auth_middleware(): void
    {
        foreach ([
            'admin.affiliate.index',
            'admin.home.settings',
            'admin.header.settings',
            'admin.footer.settings',
            'admin.banners',
            'admin.flash-sales',
            'admin.coupons.index',
            'admin.customers.index',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] was not registered.");
            $this->assertContains('auth:admin', $route->gatherMiddleware());
        }
    }

    public function test_registered_website_admin_pages_use_module_livewire_aliases(): void
    {
        foreach ([
            'Modules/Website/resources/views/pages/admin/flash-sale/index.blade.php',
            'Modules/Website/resources/views/pages/admin/home/index.blade.php',
            'Modules/Website/resources/views/pages/admin/banner/index.blade.php',
            'Modules/Website/resources/views/pages/admin/header/index.blade.php',
            'Modules/Website/resources/views/pages/admin/footer/index.blade.php',
            'Modules/Website/resources/views/pages/admin/affiliate/product-commissions.blade.php',
        ] as $path) {
            $contents = file_get_contents(base_path($path));

            $this->assertStringNotContainsString("@livewire('admin.", $contents, "{$path} uses a non-module Livewire alias.");
            $this->assertStringNotContainsString('@livewire("admin.', $contents, "{$path} uses a non-module Livewire alias.");
        }
    }
}
