<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementWorkspaceContractTest extends TestCase
{
    public function test_loan_workspace_routes_are_scoped_to_application_and_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));

        $this->assertStringContainsString('client.application:loans', $routes);
        $this->assertStringContainsString("Route::post('/lenders'", $routes);
        $this->assertStringContainsString("Route::post('/contracts'", $routes);
        $this->assertStringContainsString("Route::put('/contracts/{contract}'", $routes);
        $this->assertStringContainsString("can('client.loans.contracts.create')", $controller);
        $this->assertStringContainsString("can('client.loans.contracts.edit')", $controller);
        $this->assertStringContainsString("Rule::unique('loan_contracts', 'contract_number')", $controller);
    }

    public function test_dashboard_uses_domain_service_and_managed_presentation(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php'));
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $service = file_get_contents(base_path('Modules/LoanManagement/Services/LoanContractService.php'));

        $this->assertStringContainsString('LoanContractService $loans', $controller);
        $this->assertStringContainsString('featurePresentation(', $controller);
        $this->assertStringContainsString("overviewPresentation['page_title'", $blade);
        $this->assertStringContainsString('<x-pwa-date', $blade);
        $this->assertStringContainsString("bcsub(", $service);
        $this->assertStringContainsString("lockForUpdate()", $service);
        $this->assertStringNotContainsString('Modules\\Finance', $service);
    }
}
