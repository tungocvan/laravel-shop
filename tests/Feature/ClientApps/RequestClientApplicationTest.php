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
        $this->assertSame('client.request.inbox', $application['features'][3]['route']);
        $this->assertSame('client.request.processed', $application['features'][4]['route']);
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

    public function test_request_approver_routes_use_web_guard_and_feature_boundaries(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        foreach ([
            'client.request.inbox' => ['apps/request/inbox', 'client.feature:request,inbox'],
            'client.request.processed' => ['apps/request/processed', 'client.feature:request,processed'],
        ] as $name => [$uri, $featureMiddleware]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertContains('auth:web', $route->gatherMiddleware(), $name);
            $this->assertContains('client.application:request', $route->gatherMiddleware(), $name);
            $this->assertContains($featureMiddleware, $route->gatherMiddleware(), $name);
            $this->assertContains(UseRequestAuthorizationGuard::class.':web', $route->gatherMiddleware(), $name);
        }
    }

    public function test_approver_inbox_component_is_channel_aware(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Approver/Inbox.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/inbox.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/request/dashboard.blade.php'));

        $this->assertStringContainsString('InteractsWithRequestAuthorization', $component);
        $this->assertStringContainsString('$this->requestActor($context)', $component);
        $this->assertStringContainsString('Gate::forUser($user)->authorize', $component);
        $this->assertStringNotContainsString("auth('admin')->id()", $component);
        $this->assertStringContainsString("\$requestGuard === 'admin'", $view);
        $this->assertStringContainsString("route('client.request.inbox')", $dashboard);
        $this->assertStringContainsString("route('client.request.processed')", $dashboard);
    }
}
