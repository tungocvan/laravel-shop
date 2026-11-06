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

    public function test_lookup_hub_checks_muasamcong_permissions_before_linking(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaLookupController.php'));
        $this->assertStringContainsString("$"."registry->find('muasamcong')", $controller);
        $this->assertStringContainsString("$"."registry->userCan($"."user", $controller);
        $this->assertStringContainsString("'drug-pricing'", $controller);
    }

    public function test_lookup_cards_keep_unimplemented_sources_noninteractive(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/lookups.blade.php'));

        $this->assertStringContainsString("route('client.pharma.dav-prices')", $blade);
        $this->assertStringContainsString('Tra cứu giá thuốc đông dược', $blade);
        $this->assertStringContainsString('Tra cứu thuốc trúng thầu', $blade);
        $this->assertStringContainsString("route('client.muasamcong.drug-pricing')", $blade);
        $this->assertStringContainsString('@if($canViewAwardPricing)', $blade);
        $this->assertStringContainsString('Tra cứu GMP', $blade);
        $this->assertStringContainsString('aria-disabled="true"', $blade);
        $this->assertStringContainsString('Sắp triển khai', $blade);
    }
}
