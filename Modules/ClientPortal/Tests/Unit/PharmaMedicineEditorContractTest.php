<?php

namespace Modules\ClientPortal\Tests\Unit;

use Tests\TestCase;

class PharmaMedicineEditorContractTest extends TestCase
{
    public function test_client_medicine_editor_routes_and_permission_guards_exist(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineEditorController.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));

        foreach (["'/products/create'", "'/products/{medicine}/edit'", "'/products/{medicine}'"] as $route) {
            $this->assertStringContainsString($route, $routes);
        }
        $this->assertStringContainsString("'client.pharma.products.'.$action", $controller);
        $this->assertStringContainsString('MedicineService $service', $controller);
        $this->assertStringContainsString("'client.pharma.products.create'", $manifest);
        $this->assertStringContainsString("'client.pharma.products.edit'", $manifest);
    }

    public function test_client_catalog_exposes_create_and_edit_actions(): void
    {
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $form = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/medicine-editor.blade.php'));

        $this->assertStringContainsString("route('client.pharma.products.create')", $list);
        $this->assertStringContainsString("route('client.pharma.products.edit'", $list);
        $this->assertStringContainsString('@csrf', $form);
        $this->assertStringContainsString("@method('PUT')", $form);
    }
}
