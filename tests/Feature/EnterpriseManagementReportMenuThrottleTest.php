<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class EnterpriseManagementReportMenuThrottleTest extends TestCase
{
    public function test_all_report_menu_write_routes_have_five_requests_per_minute_limit(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $expected = [
            ['POST', 'demo/{demo}/menu'],
            ['POST', 'demo/{demo}/menu/presets'],
            ['PUT', 'demo/{demo}/menu/presets/{preset}'],
            ['DELETE', 'demo/{demo}/menu/presets/{preset}'],
        ];

        foreach ($expected as [$method, $uri]) {
            $matches = $routes->filter(fn (Route $route) =>
                $route->uri() === $uri
                && in_array($method, $route->methods(), true)
                && str_contains((string) ($route->wheres['demo'] ?? ''), 'bao-cao-quan-tri-doanh-nghiep')
            );

            $this->assertCount(1, $matches, "Expected one {$method} {$uri} route for enterprise report.");

            foreach ($matches as $route) {
                $this->assertContains(
                    'throttle:5,1',
                    $route->gatherMiddleware(),
                    "Missing throttle on {$method} {$uri}"
                );
            }
        }
    }
}
