<?php

namespace Tests\Feature\Post;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PostRouteConfigurationTest extends TestCase
{
    public function test_post_admin_routes_are_registered_with_permission_middleware(): void
    {
        $routes = [
            'admin.posts.index' => 'permission:view_post,admin',
            'admin.posts.create' => 'permission:create_post,admin',
            'admin.posts.edit' => 'permission:edit_post,admin',
        ];

        foreach ($routes as $name => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} is not registered.");
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains($permissionMiddleware, $route->gatherMiddleware());
        }
    }

    public function test_post_edit_route_constrains_id_to_number(): void
    {
        $route = Route::getRoutes()->getByName('admin.posts.edit');

        $this->assertSame('[0-9]+', $route?->wheres['id'] ?? null);
    }

    public function test_broken_post_api_route_is_not_registered(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('api.post.index'));
    }
}
