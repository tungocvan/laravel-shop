<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Tests\TestCase;

class MuasamcongHistoryTest extends TestCase
{
    public function test_history_route_requires_client_application_and_history_feature(): void
    {
        $route = Route::getRoutes()->getByName('client.muasamcong.history');

        $this->assertNotNull($route);
        $this->assertSame('apps/muasamcong/history', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:muasamcong', $route->gatherMiddleware());
        $this->assertContains('client.feature:muasamcong,history', $route->gatherMiddleware());
    }

    public function test_history_feature_is_exposed_by_muasamcong_manifest(): void
    {
        $application = app(ApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);
        $this->assertArrayHasKey('history', $application['features']);
        $this->assertSame('client.muasamcong.history', $application['features']['history']['route']);
        $this->assertSame('client.muasamcong.history.view', $application['features']['history']['permission']);
    }
}
