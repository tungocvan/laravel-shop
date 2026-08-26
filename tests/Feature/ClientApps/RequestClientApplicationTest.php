<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Request\Http\Middleware\UseRequestAuthorizationGuard;
use Tests\TestCase;

class RequestClientApplicationTest extends TestCase
{
    public function test_registry_discovers_request_adapter_manifest_when_request_module_is_enabled(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $application = app(ApplicationRegistry::class)->find('request');

        $this->assertNotNull($application);
        $this->assertSame('Request', $application['module']);
        $this->assertSame('Đề nghị & Phê duyệt', $application['name']);
        $this->assertSame('client.request.dashboard', $application['route']);
        $this->assertSame('client.request.access', $application['permission']);
        $this->assertSame(
            ['overview', 'create', 'mine', 'inbox', 'processed'],
            collect($application['features'])->pluck('key')->all(),
        );
    }

    public function test_request_application_disappears_when_source_module_is_disabled(): void
    {
        config()->set('modules.registry.Request.enabled', false);

        $this->assertNull(app(ApplicationRegistry::class)->find('request'));
    }

    public function test_guest_is_redirected_from_request_dashboard(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $this->get('/apps/request')->assertRedirect(route('client.apps.login'));
    }

    public function test_request_dashboard_route_uses_web_client_and_request_authorization_boundaries(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $route = Route::getRoutes()->getByName('client.request.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('apps/request', $route->uri());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:request', $route->gatherMiddleware());
        $this->assertContains('client.feature:request,overview', $route->gatherMiddleware());
        $this->assertContains(UseRequestAuthorizationGuard::class.':web', $route->gatherMiddleware());
    }
}
