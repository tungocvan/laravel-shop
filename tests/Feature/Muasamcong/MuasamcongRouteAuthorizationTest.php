<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MuasamcongRouteAuthorizationTest extends TestCase
{
    public function test_search_routes_use_admin_prefix_and_view_permission(): void
    {
        $index = Route::getRoutes()->getByName('muasamcong.index');
        $hsmt = Route::getRoutes()->getByName('muasamcong.hsmt');
        $contractors = Route::getRoutes()->getByName('muasamcong.contractors');
        $synced = Route::getRoutes()->getByName('muasamcong.synced');
        $wishlist = Route::getRoutes()->getByName('muasamcong.wishlist');

        $this->assertNotNull($index);
        $this->assertNotNull($hsmt);
        $this->assertNotNull($contractors);
        $this->assertNotNull($synced);
        $this->assertNotNull($wishlist);
        $this->assertSame('admin/muasamcong', $index->uri());
        $this->assertSame('admin/muasamcong/hsmt', $hsmt->uri());
        $this->assertSame('admin/muasamcong/contractors', $contractors->uri());
        $this->assertSame('admin/muasamcong/synced', $synced->uri());
        $this->assertSame('admin/muasamcong/wishlist', $wishlist->uri());

        foreach ([$index, $hsmt, $contractors, $synced, $wishlist] as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:admin', $middleware);
            $this->assertContains('permission:view_muasamcong,admin', $middleware);
            $this->assertNotContains('permission:muasamcong.config.manage,admin', $middleware);
        }
    }

    public function test_config_route_uses_dedicated_management_permission(): void
    {
        $route = Route::getRoutes()->getByName('muasamcong.config');

        $this->assertNotNull($route);
        $this->assertSame('admin/muasamcong/config', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:muasamcong.config.manage,admin', $route->gatherMiddleware());
        $this->assertNotContains('permission:view_muasamcong,admin', $route->gatherMiddleware());
    }

    public function test_api_routes_remain_unchanged_and_module_routes_are_not_duplicated(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->filter(fn (string $uri): bool => str_contains($uri, 'muasamcong'))
            ->values();

        $this->assertCount(8, $uris);
        $this->assertContains('api/muasamcong', $uris);
        $this->assertContains('api/muasamcong/search-pricing', $uris);
        $this->assertContains('admin/muasamcong', $uris);
        $this->assertContains('admin/muasamcong/hsmt', $uris);
        $this->assertContains('admin/muasamcong/contractors', $uris);
        $this->assertContains('admin/muasamcong/synced', $uris);
        $this->assertContains('admin/muasamcong/wishlist', $uris);
        $this->assertContains('admin/muasamcong/config', $uris);
        $this->assertNotContains('muasamcong', $uris);
        $this->assertNotContains('muasamcong/hsmt', $uris);
        $this->assertNotContains('muasamcong/contractors', $uris);
        $this->assertNotContains('muasamcong/synced', $uris);
        $this->assertNotContains('muasamcong/wishlist', $uris);
        $this->assertNotContains('muasamcong/config', $uris);
    }
}
