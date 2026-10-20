<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UnifiedSystemCenterRouteTest extends TestCase
{
    public function test_unified_workspace_allows_either_runtime_or_environment_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.index');

        $this->assertNotNull($route);
        $this->assertSame('admin/system', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.manage|system.env.view,admin', $route->gatherMiddleware());
    }

    public function test_legacy_environment_entry_point_keeps_its_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.env');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/settings/env', $route->uri());
        $this->assertContains('permission:system.env.view,admin', $route->gatherMiddleware());
    }
}
