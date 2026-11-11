<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Http\Middleware\EnsureApplicationAccess;
use Tests\TestCase;

final class LoanManagementRouteCacheContractTest extends TestCase
{
    public function test_loan_routes_are_registered_without_boot_time_module_state_guard(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));

        $this->assertIsString($routes);
        $this->assertStringContainsString("->prefix('apps/loans')->name('client.loans.')", $routes);
        $this->assertStringContainsString("->name('dashboard')", $routes);
        $this->assertStringContainsString("'client.application:loans'", $routes);
        $this->assertStringNotContainsString("config('modules.registry.LoanManagement.enabled'", $routes);
    }

    public function test_disabled_application_is_still_rejected_at_request_time(): void
    {
        $middleware = file_get_contents(base_path('Modules/ClientPortal/Http/Middleware/EnsureApplicationAccess.php'));
        $registry = file_get_contents(base_path('Modules/ClientPortal/Services/ApplicationRegistry.php'));

        $this->assertIsString($middleware);
        $this->assertIsString($registry);
        $this->assertStringContainsString('$registry->find($application)', $middleware);
        $this->assertStringContainsString('abort_if($manifest === null, 404)', $middleware);
        $this->assertStringContainsString("! (\$source['enabled'] ?? false)", $registry);
    }
}
