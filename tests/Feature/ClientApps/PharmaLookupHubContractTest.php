<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PharmaLookupHubContractTest extends TestCase
{
    public function test_lookup_hub_has_guarded_route_and_keeps_dav_routes(): void
    {
        $hub = Route::getRoutes()->getByName('client.pharma.lookups');
        $dav = Route::getRoutes()->getByName('client.pharma.dav-prices');
        $import = Route::getRoutes()->getByName('client.pharma.dav-prices.import.store');

        $this->assertNotNull($hub);
        $this->assertSame('apps/pharma/lookups', $hub->uri());
        $this->assertContains('GET', $hub->methods());
        $this->assertContains('client.feature:pharma,lookups', $hub->gatherMiddleware());
        $this->assertNotNull($dav);
        $this->assertNotNull($import);
        $this->assertContains('POST', $import->methods());
    }

    public function test_lookup_manifest_uses_existing_permission_and_managed_presentation(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertSame('Tra cứu', $manifest['navigation']['dav-prices']['name']);
        $this->assertSame('client.pharma.lookups', $manifest['navigation']['dav-prices']['route']);
        $this->assertSame('client.pharma.dav-prices.view', $manifest['features']['lookups']['permission']);
        $this->assertSame('client.pharma.lookups', $manifest['features']['lookups']['route']);
        foreach (['eyebrow', 'page_title', 'page_description'] as $key) {
            $this->assertNotEmpty($manifest['features']['lookups'][$key]);
        }
    }

    public function test_award_lookup_route_and_adapter_do_not_depend_on_muasamcong_module(): void
    {
        $route = Route::getRoutes()->getByName('client.pharma.award-prices');
        $this->assertNotNull($route);
        $this->assertSame('apps/pharma/award-prices', $route->uri());
        $this->assertContains('client.feature:pharma,award-prices', $route->gatherMiddleware());

        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $this->assertSame('client.pharma.dav-prices.view', $manifest['features']['award-prices']['permission']);

        $source = file_get_contents(base_path('Modules/Pharma/Services/PublicAwardPriceLookup.php'));
        $this->assertStringNotContainsString('Modules\\\\Muasamcong', $source);
        $this->assertStringNotContainsString("config('muasamcong", $source);
        $this->assertStringContainsString("'verify' => true", $source);
    }

    public function test_pharma_dashboard_excludes_lookup_children_without_disabling_their_routes(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringContainsString(
            "['dav-prices', 'traditional-prices', 'award-prices', 'gmp', 'domestic-gmp', 'drug-registrations', 'traditional-drug-registrations']",
            $controller
        );
        $this->assertStringContainsString('->reject(fn (array $feature)', $controller);
        $this->assertSame('client.pharma.lookups', $manifest['features']['lookups']['route']);

        foreach (['dav-prices', 'traditional-prices', 'award-prices', 'gmp', 'domestic-gmp'] as $key) {
            $this->assertArrayHasKey($key, $manifest['features']);
            $this->assertNotNull(Route::getRoutes()->getByName($manifest['features'][$key]['route']));
        }

        $hub = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookups.blade.php'));
        foreach (['dav-prices', 'traditional-prices', 'award-prices', 'gmp', 'domestic-gmp'] as $key) {
            $this->assertStringContainsString("route('client.pharma.".$key."')", $hub);
        }
    }

    public function test_registration_search_has_reset_action_and_is_only_listed_in_lookup_hub(): void
    {
        $form = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/drug-registrations.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $hub = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookups.blade.php'));
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringContainsString('aria-label="Xóa bộ lọc tìm kiếm"', $form);
        $this->assertStringContainsString('href="{{ route($routeBase) }}"', $form);
        $this->assertStringContainsString('Xóa lọc', $form);
        foreach (['drug-registrations', 'traditional-drug-registrations'] as $key) {
            $this->assertStringContainsString("'".$key."'", $dashboard);
            $this->assertArrayHasKey($key, $manifest['features']);
            $this->assertArrayNotHasKey($key, $manifest['navigation']);
            $this->assertStringContainsString("route('client.pharma.".$key."')", $hub);
            $this->assertNotNull(Route::getRoutes()->getByName($manifest['features'][$key]['route']));
        }
    }

    public function test_lookup_cards_link_to_all_four_independent_sources(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookups.blade.php'));

        $this->assertStringContainsString("route('client.pharma.dav-prices')", $blade);
        $this->assertStringContainsString('Tra cứu giá thuốc đông dược', $blade);
        $this->assertStringContainsString('Tra cứu thuốc trúng thầu', $blade);
        $this->assertStringContainsString("route('client.pharma.award-prices')", $blade);
        $this->assertStringNotContainsString('canViewAwardPricing', $blade);
        $this->assertStringContainsString('Tra cứu GMP', $blade);
        $this->assertStringContainsString("route('client.pharma.gmp')", $blade);
        $this->assertStringContainsString("route('client.pharma.domestic-gmp')", $blade);
        $this->assertStringNotContainsString('aria-disabled="true"', $blade);
        $this->assertStringNotContainsString('Sắp triển khai', $blade);
    }
}
