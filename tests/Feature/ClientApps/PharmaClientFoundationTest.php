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
        $this->assertSame('overview', $application['hub']['key']);
        $this->assertSame('client.pharma.overview.view', $application['hub']['permission']);
        $this->assertSame('Pharma PWA', $application['hub']['eyebrow']);
        $this->assertSame('Không gian làm việc Pharma', $application['hub']['title']);
        $this->assertSame('Làm việc theo phạm vi được giao', $application['hub']['supporting']['title']);
        $this->assertSame(
            ['products', 'price-lists', 'bid-awards', 'commercial', 'orders', 'inventory', 'commissions'],
            collect($application['features'])->pluck('key')->all(),
        );
        $this->assertSame('overview', collect($application['navigation'])->firstWhere('key', 'overview')['key']);
        $this->assertSame('client.pharma.dashboard', collect($application['navigation'])->firstWhere('key', 'overview')['route']);
        $this->assertNull(collect($application['features'])->firstWhere('key', 'overview'));
        $commercial = collect($application['features'])->firstWhere('key', 'commercial');
        $this->assertNotNull($commercial);
        $this->assertSame('client.pharma.commercial', $commercial['route']);
        $this->assertTrue(Route::has($commercial['route']));
        $orders = collect($application['features'])->firstWhere('key', 'orders');
        $this->assertNotNull($orders);
        $this->assertSame('client.pharma.orders', $orders['route']);
        $this->assertSame('client.pharma.orders', $orders['permission']);
        $this->assertTrue(Route::has($orders['route']));
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
            'client.pharma.orders',
            'client.pharma.inventory.view',
            'client.pharma.inventory.receipts',
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

    public function test_hub_permission_is_resolved_without_restoring_overview_business_feature(): void
    {
        $middleware = file_get_contents(base_path('Modules/ClientPortal/Http/Middleware/EnsureFeatureAccess.php'));
        $permissions = file_get_contents(base_path('Modules/ClientPortal/Services/ApplicationPermissionService.php'));

        $this->assertStringContainsString("\$manifest['hub']", $middleware);
        $this->assertStringContainsString("['permission']", $middleware);
        $this->assertStringContainsString("\$application['hub']", $permissions);
        $this->assertNull(collect(app(ApplicationRegistry::class)->find('pharma')['features'])->firstWhere('key', 'overview'));
    }

    public function test_pwa_404_home_action_returns_to_application_launcher(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/errors/404.blade.php'));

        $this->assertStringContainsString("str_starts_with(\$requestPath, 'apps/')", $view);
        $this->assertStringContainsString("str_starts_with(\$requestPath, 'my-apps/')", $view);
        $this->assertStringContainsString("url('/my-apps')", $view);
        $this->assertStringContainsString('Trang chủ', $view);
        $this->assertStringContainsString("route('home')", $view);
        $this->assertStringContainsString("route('product.list')", $view);
    }

    public function test_pwa_native_touch_standard_has_reusable_accessible_primitive(): void
    {
        $component = file_get_contents(base_path('Modules/ClientPortal/resources/views/components/native-touch.blade.php'));
        $standard = file_get_contents(base_path('docs/modules/ClientPortal/PWA_APPLICATION_STANDARD.md'));

        $this->assertStringContainsString("active:scale-[0.985]", $component);
        $this->assertStringContainsString('touch-manipulation', $component);
        $this->assertStringContainsString('[-webkit-tap-highlight-color:transparent]', $component);
        $this->assertStringContainsString('motion-reduce:transform-none', $component);
        $this->assertStringContainsString('disabled', $component);
        $this->assertStringContainsString('aria-disabled="true"', $component);
        $this->assertStringContainsString('<x-native-touch>', $standard);
        $this->assertStringContainsString('Native touch interaction contract', $standard);
        $this->assertStringContainsString('focus-visible', $standard);
        $this->assertStringContainsString('disabled', $standard);
    }

    public function test_pwa_closeout_docs_preserve_hub_navigation_and_native_touch_boundaries(): void
    {
        $adminSettings = file_get_contents(base_path('docs/modules/ClientPortal/PWA_ADMIN_SETTINGS.md'));
        $workflow = file_get_contents(base_path('docs/modules/ClientPortal/PWA_AI_WORKFLOW.md'));

        $this->assertStringContainsString('application.{key}.hub', $adminSettings);
        $this->assertStringContainsString('application.{key}.navigation', $adminSettings);
        $this->assertStringContainsString('pwa.bottom_navigation', $adminSettings);
        $this->assertStringContainsString('clientportal.pwa.bottom_navigation.themes', $adminSettings);
        $this->assertStringContainsString('single-surface mobile dock', $adminSettings);
        $this->assertStringContainsString('does not require restoring Overview as a business capability', $adminSettings);
        $this->assertStringContainsString('Native touch interaction gate', $workflow);
        $this->assertStringContainsString('<x-native-touch>', $workflow);
    }

    public function test_pharma_foundation_does_not_reuse_admin_presentation_or_facade_calls_in_blade(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/dashboard.blade.php'));

        $this->assertStringContainsString('ClientPortal::applications.pharma.dashboard', $controller);
        $this->assertStringContainsString('Route::has($routeName)', $controller);
        $dashboardMethod = substr($controller, strrpos($controller, 'public function dashboard'));
        $this->assertStringNotContainsString('featurePresentation', $dashboardMethod);
        $this->assertStringNotContainsString('$overviewFeature', $dashboardMethod);
        $this->assertStringContainsString("ClientPortal::layouts.application", $view);
        $this->assertStringContainsString("route_available", $view);
        $this->assertStringContainsString("\$hubPresentation['eyebrow']", $view);
        $this->assertStringContainsString("\$hubPresentation['supporting_title']", $view);
        $this->assertStringNotContainsString('<h2 class="font-black text-slate-950">Ranh giới Foundation</h2>', $view);
        $this->assertStringNotContainsString('IlluminateSupportFacadesRoute', $view);
        $this->assertStringNotContainsString('Route::has(', $view);
        $this->assertStringNotContainsString('Admin::layouts.master', $view);
        $this->assertStringNotContainsString('auth:admin', $controller);
    }
}
