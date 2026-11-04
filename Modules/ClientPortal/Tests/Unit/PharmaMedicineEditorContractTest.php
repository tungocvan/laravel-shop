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

    public function test_supplier_commercial_editor_has_four_tabs_and_write_permission(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/medicine-supplier-conditions.blade.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineRelationsController.php'));

        // Blade renders the four buttons from a loop; source contains dynamic attributes.
        foreach (['supplier', 'commercial', 'distribution', 'commitments'] as $tab) {
            $this->assertStringContainsString("'".$tab."'=>'", $view);
        }
        $this->assertStringContainsString('data-supplier-tab="{{ $tab }}"', $view);
        $this->assertStringContainsString('data-supplier-panel="supplier"', $view);
        $this->assertStringContainsString('data-supplier-panel="commercial"', $view);
        $this->assertStringContainsString('data-supplier-panel="distribution"', $view);
        $this->assertStringContainsString('data-supplier-panel="commitments"', $view);
        $this->assertStringContainsString("route('client.pharma.products.supplier-conditions.save'", $view);
        $this->assertStringContainsString("name('products.supplier-conditions.save')", $routes);
        $this->assertStringContainsString("'client.pharma.products.supplier-conditions.manage'", $manifest);
        $this->assertStringContainsString("'supplier-conditions.manage'", $controller);
        $this->assertStringContainsString("hasPartnerType('supplier')", $controller);
        $this->assertStringContainsString("supplierTrackings()->findOrFail(", $controller);
    }

    public function test_hssp_editor_uses_tabs_and_scoped_update_route(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/medicine-hssp.blade.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineRelationsController.php'));

        foreach (['profile', 'storage', 'documents', 'review'] as $tab) {
            $this->assertStringContainsString('data-hssp-tab="'.$tab.'"', $view);
            $this->assertStringContainsString('data-hssp-panel="'.$tab.'"', $view);
        }
        $this->assertStringContainsString('<x-dossier.editor', $view);
        $this->assertStringContainsString("route('client.pharma.products.hssp.update'", $view);
        $this->assertStringContainsString("'/products/{medicine}/hssp'", $routes);
        $this->assertStringContainsString("'client.pharma.products.hssp.update'", $manifest);
        $this->assertStringContainsString("'hssp.update'", $controller);
    }

    public function test_client_hssp_and_supplier_actions_are_scoped_to_web_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineRelationsController.php'));
        $catalog = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $hssp = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/medicine-hssp.blade.php'));

        $this->assertStringContainsString("'/products/{medicine}/hssp'", $routes);
        $this->assertStringContainsString("'/products/{medicine}/supplier-conditions'", $routes);
        $this->assertStringContainsString("'client.pharma.products.hssp.create'", $manifest);
        $this->assertStringContainsString("'supplier-pricing'", $controller);
        $this->assertStringContainsString('MedicineProfile::STATUS_INCOMPLETE', $controller);
        $this->assertStringContainsString('createFromTemplate(', $controller);
        $this->assertStringContainsString("route('client.pharma.products.hssp'", $catalog);
        $this->assertStringContainsString("route('client.pharma.products.supplier-conditions'", $catalog);
        $this->assertStringContainsString("route('client.pharma.products.hssp.store'", $hssp);
    }

    public function test_catalog_toolbar_is_compact_and_action_menu_is_not_clipped(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringNotContainsString('Import Excel & lịch sử staging', $view);
        $this->assertStringNotContainsString('Xuất báo cáo', $view);
        $this->assertStringContainsString('Mẫu xuất Excel', $view);
        $this->assertStringContainsString('Tìm kiếm & bộ lọc', $view);
        $this->assertStringContainsString('data-medicine-actions-menu', $view);
        $this->assertStringContainsString('document.body.appendChild(menu)', $view);
        $this->assertStringContainsString('window.addEventListener(\'scroll\', closeActions, true)', $view);
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

    public function test_new_import_approval_allocates_medicine_code_and_default_sku_safely(): void
    {
        $service = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/ClientMedicineImportCreator.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import-batch.blade.php'));
        $stager = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportStager.php'));

        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('MedicineImportRow::CLASS_NEW', $service);
        $this->assertStringContainsString('$this->medicines->store(', $service);
        $this->assertStringContainsString('$this->variants->createFirst(', $service);
        $this->assertStringContainsString('medicine_code_reference', $service);
        $this->assertStringContainsString('registration_exists_without_roundtrip_reference', $stager);
        $this->assertStringContainsString('function approveNew(', $controller);
        $this->assertStringContainsString("'client.pharma.products.create-variant'", $controller);
        $this->assertStringContainsString("'/products/import/batches/{batch}/approve-new'", $routes);
        $this->assertStringContainsString("route('client.pharma.products.import.approve-new'", $view);
    }

    public function test_client_import_has_dedicated_page_history_and_batch_review(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $catalog = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));
        $import = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import.blade.php'));
        $review = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import-batch.blade.php'));

        $this->assertStringContainsString('function importPage(', $controller);
        $this->assertStringContainsString('function importBatch(', $controller);
        $this->assertStringContainsString('where(\'created_by\', (int) $user->id)', $controller);
        $this->assertStringContainsString("'clientportal_pharma'", $controller);
        $this->assertStringContainsString("'/products/import/batches/{batch}'", $routes);
        $this->assertStringContainsString("route('client.pharma.products.import.page')", $catalog);
        $this->assertStringContainsString("route('client.pharma.products.import')", $import);
        $this->assertStringContainsString('Lịch sử import gần đây', $import);
        $this->assertStringContainsString('resolution_reason', $review);
        $this->assertStringContainsString('Chưa ghi vào Medicine Master', $review);
    }

    public function test_roundtrip_import_references_are_matched_without_automatic_writes(): void
    {
        $mapper = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportMapper.php'));
        $stager = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportStager.php'));

        $this->assertStringContainsString("'medicine_code_reference'", $mapper);
        $this->assertStringContainsString("'sku_reference'", $mapper);
        $this->assertStringContainsString('where(\'medicine_code\', $medicineCode)', $stager);
        $this->assertStringContainsString('where(\'sku\', $skuCode)', $stager);
        $this->assertStringContainsString("'roundtrip_reference_not_found'", $stager);
        $this->assertStringContainsString("'roundtrip_sku_medicine_mismatch'", $stager);
        $this->assertStringContainsString("'roundtrip_identity_mismatch'", $stager);
        $this->assertStringContainsString("'roundtrip_repeated_medicine_reference'", $stager);
        $this->assertStringContainsString("'roundtrip_existing_medicine'", $stager);
    }

    public function test_roundtrip_excel_export_uses_staging_import_headers_and_permission(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/products.blade.php'));

        $this->assertStringContainsString('function roundtripExport(', $controller);
        $this->assertStringContainsString("'client.pharma.products.export'", $controller);
        $this->assertStringContainsString("'danh-muc-thuoc-import-lai.xlsx'", $controller);
        $this->assertStringContainsString("'Giấy phép lưu hành sản phẩm'", $controller);
        $this->assertStringContainsString("'Nồng độ - Hàm lượng'", $controller);
        $this->assertStringContainsString("'/products/export/roundtrip'", $routes);
        $this->assertStringContainsString("route('client.pharma.products.export.roundtrip')", $view);
        $this->assertStringContainsString('Chưa ghi bất kỳ thay đổi nào vào Medicine Master', $view);
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
        $identityAssignment = strpos($service, '$identityKey = $this->skuGenerator->variantIdentity($identityAttributes)');
        $variantInsert = strpos($service, 'return $medicine->variants()->create([');
        $identityPersistence = strpos($service, "'variant_identity_key' => ".'$identityKey', $variantInsert);
        $this->assertNotFalse($identityAssignment);
        $this->assertNotFalse($variantInsert);
        $this->assertNotFalse($identityPersistence);
        $this->assertLessThan($variantInsert, $identityAssignment);
        $this->assertGreaterThan($variantInsert, $identityPersistence);

        $this->assertStringContainsString("'variant_identity_key' => \$identityKey", $service);
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
