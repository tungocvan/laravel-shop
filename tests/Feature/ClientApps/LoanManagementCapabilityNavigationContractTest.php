<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementCapabilityNavigationContractTest extends TestCase
{
    public function test_all_workspace_capabilities_have_managed_presentation_and_distinct_routes(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/LoanManagement/manifest.php');
        $expected = ['overview', 'lenders', 'contracts', 'payments', 'reminders', 'insurance'];

        $this->assertSame($expected, array_keys($manifest['features']));
        foreach ($expected as $key) {
            $feature = $manifest['features'][$key];
            $this->assertSame($feature['route'], $manifest['navigation'][$key]['route']);
            $this->assertSame($feature['permission'], $manifest['navigation'][$key]['permission']);
            foreach (['eyebrow', 'page_title', 'page_description', 'sort_order'] as $field) {
                $this->assertNotEmpty($feature[$field], "{$key}: {$field}");
            }
        }
    }

    public function test_browse_routes_are_added_without_changing_existing_mutation_endpoints(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/capability.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));

        $this->assertStringContainsString('Route::get(\'/\'.$workspace', $routes);
        foreach (["Route::post('/lenders'", "Route::post('/contracts'", "Route::put('/contracts/{contract}'", "Route::delete('/contracts/{contract}'"] as $endpoint) {
            $this->assertStringContainsString($endpoint, $routes);
        }
        $this->assertStringContainsString("can(self::WORKSPACES[", $controller);
        $this->assertStringContainsString("featurePresentation('loans'", $controller);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        $this->assertStringContainsString("route('client.loans.dashboard')", $view);
        $this->assertStringContainsString("aria-label=\"Điều hướng khoản vay\"", $dashboard);
        $this->assertStringNotContainsString('aria-label="Không gian nghiệp vụ khoản vay"', $dashboard);
    }
}
