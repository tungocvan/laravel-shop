<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PartnerBusinessLookupPwaContractTest extends TestCase
{
    public function test_lookup_has_managed_presentation_and_independent_permissions(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Partner/manifest.php');
        $lookup = $manifest['features']['lookup'];
        $this->assertSame('client.partners.lookup', $lookup['permission']);
        $this->assertSame('client.partners.sync', $lookup['actions']['sync']['permission']);
        foreach (['eyebrow', 'page_title', 'page_description'] as $field) {
            $this->assertNotEmpty($lookup[$field]);
        }
        $this->assertSame('client.partners.lookup', $manifest['navigation']['lookup']['permission']);
        $config = require base_path('Modules/Partner/config/module.php');
        $this->assertContains('client.partners.lookup', $config['permissions_by_guard']['web']);
        $this->assertContains('client.partners.sync', $config['permissions_by_guard']['web']);
    }

    public function test_lookup_route_uses_web_guard_and_feature_authorization(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Partner/routes.php'));
        $this->assertStringContainsString("'auth:web'", $routes);
        $this->assertStringContainsString("'client.feature:partners,lookup'", $routes);
        $this->assertStringContainsString("'/business-lookup'", $routes);
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Partner/Http/Controllers/PartnerApplicationController.php'));
        $this->assertStringContainsString("context('lookup'", $controller);
    }

    public function test_lookup_reuses_domain_services_and_requires_explicit_sync_permission(): void
    {
        $component = file_get_contents(base_path('Modules/Partner/Livewire/BusinessLookup.php'));
        $this->assertStringContainsString('MultiSourceBusinessLookupService', $component);
        $this->assertStringContainsString('PartnerSyncService', $component);
        $this->assertStringContainsString("'client.partners.lookup'", $component);
        $this->assertStringContainsString("'client.partners.sync'", $component);
        $this->assertStringContainsString("'client.partners.edit'", $component);
        $this->assertStringContainsString("'client.partners.create'", $component);
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/partners/business-lookup.blade.php'));
        $this->assertStringContainsString("@livewire('partner.business-lookup')", $view);
        $this->assertStringContainsString("pagePresentation['page_title']", $view);
        $lookupView = file_get_contents(base_path('Modules/Partner/resources/views/livewire/business-lookup.blade.php'));
        $this->assertStringContainsString('wire:model.live.debounce.400ms="query"', $lookupView);
        $this->assertStringContainsString('wire:submit="search"', $lookupView);
        $this->assertStringNotContainsString('wire:model="query"', $lookupView);
    }
}
