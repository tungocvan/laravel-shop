<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryCapabilityTest extends TestCase
{
    public function test_inventory_capability_is_routed_through_client_portal_and_canonical_pharma_workspace(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryWorkspace.php');

        $this->assertStringContainsString("Route::get('/inventory'", $routes);
        $this->assertStringContainsString("->name('inventory')", $routes);
        $this->assertStringContainsString("'route' => 'client.pharma.inventory'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.inventory.view'", $manifest);
        $this->assertStringContainsString('UserInventoryWorkspace $workspace', $controller);
        $this->assertStringContainsString("abort_unless(\$registry->userCan(\$user, 'client.pharma.inventory.view'), 403)", $controller);
        $this->assertStringContainsString('InventoryBalance::query()', $workspace);
        $this->assertStringContainsString('SupplierTracking::query()', $workspace);
        $this->assertStringNotContainsString('InventoryBalance::query()', $controller);
        $this->assertStringNotContainsString('SupplierTracking::query()', $controller);
    }

    public function test_inventory_pwa_is_read_only_responsive_and_uses_managed_presentation(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory.blade.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringNotContainsString("Route::post('/inventory", $routes);
        $this->assertStringNotContainsString("Route::put('/inventory", $routes);
        $this->assertStringNotContainsString("Route::delete('/inventory", $routes);
        $this->assertStringContainsString("featurePresentation['page_title']", $view);
        $this->assertStringContainsString("featurePresentation['page_description']", $view);
        $this->assertStringContainsString('xl:hidden', $view);
        $this->assertStringContainsString('xl:block', $view);
        $this->assertStringContainsString('25 / trang', $view);
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString("'page_title' => 'Tồn kho Pharma'", $manifest);
    }
}
