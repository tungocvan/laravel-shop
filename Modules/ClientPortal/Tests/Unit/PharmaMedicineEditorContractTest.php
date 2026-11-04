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
        $this->assertStringContainsString("'client.pharma.products.'.\$action", $controller);
        $this->assertStringContainsString('MedicineService $service', $controller);
        $this->assertStringContainsString("'client.pharma.products.create'", $manifest);
        $this->assertStringContainsString("'client.pharma.products.edit'", $manifest);
    }

    public function test_client_catalog_includes_master_records_without_variants(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString("'masterOnlyMedicines'", $controller);
        $this->assertStringContainsString("->whereDoesntHave('variants')", $controller);
        $this->assertStringContainsString('$masterOnlyMedicines->total()', $view);
        $this->assertStringContainsString('Chưa có SKU', $view);
        $this->assertStringContainsString("route('client.pharma.products.edit', \$master->id)", $view);
    }

    public function test_explicit_sku_creation_is_guarded_and_does_not_autocreate_on_medicine_save(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineEditorController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineVariantCreationService.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/variant-create.blade.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));

        $this->assertStringContainsString("'/products/{medicine}/variants/create'", $routes);
        $this->assertStringContainsString("'/products/{medicine}/variants'", $routes);
        $this->assertStringContainsString("'create-variant'", $controller);
        $this->assertStringContainsString("'client.pharma.products.create-variant'", $manifest);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('variantIdentity($identityAttributes)', $service);
        $this->assertStringContainsString("'variant_identity_key' => $identityKey", $service);
        $this->assertStringContainsString("'sku_basis_hash' =>", $service);
        $this->assertStringContainsString("->variants()->exists()", $service);
        $this->assertStringContainsString("where('sku', \$sku)", $service);
        $this->assertStringContainsString("route('client.pharma.products.variants.store'", $view);
        $this->assertStringContainsString('@csrf', $view);
    }

    public function test_client_catalog_exposes_create_and_edit_actions(): void
    {
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $form = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/medicine-editor.blade.php'));

        $this->assertStringContainsString("route('client.pharma.products.create')", $list);
        $this->assertStringContainsString("route('client.pharma.products.edit'", $list);
        $this->assertStringContainsString('@csrf', $form);
        $this->assertStringContainsString("@method('PUT')", $form);
        $this->assertStringContainsString('data-medicine-tabs', $form);
        $this->assertStringContainsString('data-tab-counter', $form);
        $this->assertStringContainsString('data-tab-next', $form);
        $this->assertStringContainsString('data-tab-previous', $form);
        $this->assertStringContainsString('medicine-panel-classification', $form);
        $this->assertStringContainsString('panels[i].hidden = !active', $form);
        $this->assertStringNotContainsString('x-show=', $form);
    }
}
