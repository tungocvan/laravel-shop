<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Tests\TestCase;

class ClientPortalFeaturePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_presentation_preserves_manifest_route_and_permission(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('muasamcong');
        $feature = collect($application['features'])->firstWhere('key', 'drug-pricing');

        $settings->updateFeaturePresentation('muasamcong', 'drug-pricing', [
            'enabled' => true,
            'name' => 'Tra cứu giá thuốc',
            'description' => 'Tên và mô tả do Admin quản trị.',
            'sort_order' => 5,
            'badge' => 'Beta',
            'maintenance' => true,
            'maintenance_message' => 'Đang nâng cấp dữ liệu.',
        ]);

        $presented = $settings->presentFeatures('muasamcong', collect([$feature]))->first();

        $this->assertSame('Tra cứu giá thuốc', $presented['name']);
        $this->assertSame('client.muasamcong.drug-pricing', $presented['route']);
        $this->assertSame('client.muasamcong.drug-pricing.view', $presented['permission']);
        $this->assertTrue($presented['maintenance']);
        $this->assertSame('Beta', $presented['badge']);
    }

    public function test_disabled_feature_is_removed_from_presented_collection(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('muasamcong');
        $feature = collect($application['features'])->firstWhere('key', 'history');

        $settings->updateFeaturePresentation('muasamcong', 'history', [
            'enabled' => false,
            'name' => $feature['name'],
            'description' => $feature['description'],
            'sort_order' => $feature['sort_order'],
            'badge' => '',
            'maintenance' => false,
            'maintenance_message' => '',
        ]);

        $this->assertTrue($settings->presentFeatures('muasamcong', collect([$feature]))->isEmpty());
    }

    public function test_feature_presentation_admin_routes_are_protected(): void
    {
        foreach (['admin.client-apps.pwa.applications.edit', 'admin.client-apps.pwa.features.update'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('permission:edit_role,admin', $route->gatherMiddleware());
        }
    }
}
