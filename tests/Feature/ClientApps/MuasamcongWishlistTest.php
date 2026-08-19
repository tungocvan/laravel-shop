<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Tests\TestCase;

class MuasamcongWishlistTest extends TestCase
{
    public function test_wishlist_routes_require_client_application_and_feature(): void
    {
        foreach (['client.muasamcong.wishlist','client.muasamcong.wishlist.store','client.muasamcong.wishlist.toggle','client.muasamcong.wishlist.destroy'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $middleware=$route->gatherMiddleware();
            $this->assertContains('auth:web',$middleware);
            $this->assertContains('client.application:muasamcong',$middleware);
            $this->assertContains('client.feature:muasamcong,wishlist',$middleware);
        }
    }

    public function test_wishlist_feature_is_exposed_by_normalized_manifest(): void
    {
        $application=app(ApplicationRegistry::class)->find('muasamcong');
        $this->assertNotNull($application);
        $feature=collect($application['features'])->firstWhere('key','wishlist');
        $this->assertNotNull($feature);
        $this->assertSame('client.muasamcong.wishlist',$feature['route']);
        $this->assertSame('client.muasamcong.wishlist.view',$feature['permission']);
    }
}
