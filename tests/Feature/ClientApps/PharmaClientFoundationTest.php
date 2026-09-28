<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Tests\TestCase;

class PharmaClientFoundationTest extends TestCase
{
    public function test_registry_discovers_pharma_application_when_source_module_is_enabled(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $application = app(ApplicationRegistry::class)->find('pharma');

        $this->assertNotNull($application);
        $this->assertSame('Pharma', $application['module']);
        $this->assertSame('client.pharma.dashboard', $application['route']);
        $this->assertSame('client.pharma.access', $application['permission']);
        $this->assertSame(['mode' => 'workspace'], $application['layout']);
        $this->assertSame(
            ['overview', 'products', 'price-lists', 'bid-awards', 'commercial', 'inventory', 'commissions'],
            collect($application['features'])->pluck('key')->all(),
        );
        $commercial = collect($application['features'])->firstWhere('key', 'commercial');
        $this->assertNotNull($commercial);
        $this->assertSame('client.pharma.commercial', $commercial['route']);
        $this->assertTrue(Route::has($commercial['route']));
    }

    public function test_pharma_application_permissions_are_managed_by_client_apps_admin(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $definitions = app(ApplicationPermissionService::class)->definitions();

        foreach ([
            'client.pharma.access',
            'client.pharma.overview.view',
            'client.pharma.products.view',
            'client.pharma.price-lists.view',
            'client.pharma.bid-awards.view',
            'client.pharma.commercial.view',
            'client.pharma.commercial.view-team',
            'client.pharma.inventory.view',
            'client.pharma.inventory.receipts',
            'client.pharma.inventory.issues',
            'client.pharma.commissions.view',
        ] as $permission) {
            $this->assertTrue($definitions->contains('name', $permission), $permission);
        }
    }

    public function test_pharma_dashboard_route_uses_web_client_authorization_boundary(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            $this->markTestSkipped('Pharma source module is disabled in this runtime.');
        }

        $route = Route::getRoutes()->getByName('client.pharma.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('apps/pharma', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:pharma', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,overview', $route->gatherMiddleware());
        $this->assertNotContains('auth:admin', $route->gatherMiddleware());
    }

    public function test_pharma_foundation_does_not_reuse_admin_presentation_or_facade_calls_in_blade(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/dashboard.blade.php'));

        $this->assertStringContainsString('ClientPortal::applications.pharma.dashboard', $controller);
        $this->assertStringContainsString('Route::has($routeName)', $controller);
        $this->assertStringContainsString("ClientPortal::layouts.application", $view);
        $this->assertStringContainsString("route_available", $view);
        $this->assertStringNotContainsString('IlluminateSupportFacadesRoute', $view);
        $this->assertStringNotContainsString('Route::has(', $view);
        $this->assertStringNotContainsString('Admin::layouts.master', $view);
        $this->assertStringNotContainsString('auth:admin', $controller);
    }
}
