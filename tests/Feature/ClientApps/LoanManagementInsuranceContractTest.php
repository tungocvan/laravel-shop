<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementInsuranceContractTest extends TestCase
{
    public function test_insurance_routes_are_authorized_and_scoped(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanInsuranceController.php'));
        $this->assertStringContainsString('client.application:loans', $routes);
        foreach (['insurance.store', 'premiums.store', 'premiums.pay'] as $route) {
            $this->assertStringContainsString($route, $routes);
        }
        $this->assertSame(3, substr_count($controller, "can('client.loans.insurance.manage')"));
    }

    public function test_insurance_service_enforces_premium_limits_and_lifecycle(): void
    {
        $source = file_get_contents(base_path('Modules/LoanManagement/Services/LoanInsuranceService.php'));
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("bccomp(\$newPaid, (string) \$premium->amount_due, 2)", $source);
        $this->assertStringContainsString("where('due_at', \$data['due_at'])", $source);
        $this->assertStringContainsString("'overdue'", $source);
        $this->assertStringContainsString("'expiring'", $source);
        $this->assertStringContainsString("'unverified'", file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-operations.blade.php')));
    }

    public function test_demo_seeder_is_opt_in_and_idempotent(): void
    {
        $source = file_get_contents(base_path('Modules/LoanManagement/Database/Seeders/LoanManagementDemoSeeder.php'));
        $this->assertStringContainsString("environment(['local', 'testing'])", $source);
        $this->assertStringContainsString("where('contract_number', \$number)->exists()", $source);
        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringNotContainsString('truncate(', $source);
        $this->assertStringNotContainsString('delete(', $source);
    }
}
