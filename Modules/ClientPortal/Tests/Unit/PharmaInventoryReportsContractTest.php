<?php

namespace Modules\ClientPortal\Tests\Unit;

use Tests\TestCase;

class PharmaInventoryReportsContractTest extends TestCase
{
    public function test_client_inventory_reports_have_scoped_routes_and_actions(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaInventoryReportsController.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));

        foreach (['inventory.movements', 'inventory.movements.export', 'inventory.export', 'inventory.opening', 'inventory.opening.template', 'inventory.opening.import'] as $route) {
            $this->assertStringContainsString("name('".$route."')", $routes);
        }
        foreach (['inventory.movements', 'inventory.export', 'inventory.opening'] as $route) {
            $this->assertStringContainsString("route('client.pharma.".$route."')", $view);
        }
        $this->assertStringContainsString('UserInventoryWorkspace $workspace', $controller);
        $this->assertStringContainsString('client.pharma.inventory.costs', $controller);
        $this->assertStringContainsString('client.pharma.inventory.opening.manage', $controller);
        $this->assertStringContainsString('pharma_inventory_transactions', $controller);
        $this->assertStringContainsString('DB::transaction(', $controller);
        $this->assertStringContainsString("'permission' => 'client.pharma.inventory.opening.manage'", $manifest);
    }
}
