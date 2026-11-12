<?php

namespace Tests\Feature\ClientApps;

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
    public function test_docker_image_and_entrypoint_do_not_precompile_project_specific_route_cache(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $entrypoint = file_get_contents(base_path('docker/entrypoint.sh'));
        $opcache = file_get_contents(base_path('docker/php/opcache.ini'));

        $this->assertIsString($dockerfile);
        $this->assertIsString($entrypoint);
        $this->assertIsString($opcache);

        // A build image must not bake routes/config from a different project's runtime.
        $this->assertDoesNotMatchRegularExpression('/php\\s+artisan\\s+(?:route:cache|config:cache|optimize)(?:\\s|$)/m', $dockerfile);
        $this->assertDoesNotMatchRegularExpression('/php\\s+artisan\\s+(?:route:cache|config:cache|optimize)(?:\\s|$)/m', $entrypoint);

        // With timestamp validation disabled, a PHP-FPM reload is a separate deployment gate.
        $this->assertMatchesRegularExpression('/^opcache\\.validate_timestamps\\s*=\\s*0\\s*$/m', $opcache);
    }
}
