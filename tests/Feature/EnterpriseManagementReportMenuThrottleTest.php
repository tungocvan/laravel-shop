<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class EnterpriseManagementReportMenuThrottleTest extends TestCase
{
    public function test_menu_write_routes_have_five_requests_per_minute_limit(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $paths = [
            'demo/{demo}/menu',
            'demo/bao-cao-quan-tri-doanh-nghiep/menu/presets',
            'demo/bao-cao-quan-tri-doanh-nghiep/menu/presets/{preset}',
        ];

        foreach ($paths as $path) {
            $matches = $routes->filter(fn (Route $route) =>
                $route->uri() === $path
                && count(array_intersect(['POST', 'PUT', 'DELETE'], $route->methods())) > 0
                && preg_match('/bao-cao-quan-tri-doanh-nghiep/', (string) ($route->wheres['demo'] ?? '')) === 1
            );

            $this->assertNotEmpty($matches->all(), "Missing write route: {$path}");

            foreach ($matches as $route) {
                $this->assertContains('throttle:5,1', $route->gatherMiddleware(), "Missing throttle on {$path}");
            }
        }
    }
}
