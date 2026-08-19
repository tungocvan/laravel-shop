<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Muasamcong\Models\SyncedExportProfile;
use Tests\TestCase;

class MuasamcongPriceListTest extends TestCase
{
    public function test_price_list_routes_require_application_and_feature_access(): void
    {
        foreach ([
            'client.muasamcong.price-list',
            'client.muasamcong.price-list.store',
            'client.muasamcong.price-list.edit',
            'client.muasamcong.price-list.recreate',
            'client.muasamcong.price-list.destroy',
            'client.muasamcong.price-list.status',
            'client.muasamcong.price-list.download',
            'client.muasamcong.price-list.share',
            'client.muasamcong.price-list.email',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:web', $middleware);
            $this->assertContains('client.application:muasamcong', $middleware);
            $this->assertContains('client.feature:muasamcong,price-list', $middleware);
        }
    }

    public function test_export_specific_routes_use_explicit_export_id_parameter(): void
    {
        foreach ([
            'client.muasamcong.price-list.edit',
            'client.muasamcong.price-list.recreate',
            'client.muasamcong.price-list.destroy',
            'client.muasamcong.price-list.status',
            'client.muasamcong.price-list.download',
            'client.muasamcong.price-list.share',
            'client.muasamcong.price-list.email',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringContainsString('{exportId}', $route->uri());
        }
    }

    public function test_price_list_feature_exposes_view_and_export_permissions(): void
    {
        $app = app(ApplicationRegistry::class)->find('muasamcong');
        $feature = collect($app['features'])->firstWhere('key', 'price-list');
        $this->assertNotNull($feature);
        $this->assertSame('client.muasamcong.price-list', $feature['route']);
        $this->assertSame('client.muasamcong.price-list.view', $feature['permission']);
        $action = collect($feature['actions'])->firstWhere('key', 'export');
        $this->assertNotNull($action);
        $this->assertSame('client.muasamcong.price-list.export', $action['permission']);
    }

    public function test_public_price_list_download_does_not_require_client_auth(): void
    {
        $route = Route::getRoutes()->getByName('public.muasamcong.price-list');
        $this->assertNotNull($route);
        $this->assertNotContains('auth:web', $route->gatherMiddleware());
    }

    public function test_client_price_list_reuses_admin_synced_export_profile_domain(): void
    {
        $this->assertSame('muasamcong_synced_export_profiles', (new SyncedExportProfile)->getTable());
        $this->assertNull(Route::getRoutes()->getByName('muasamcong.price-list-profiles.index'));
    }
}
