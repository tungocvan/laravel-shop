<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaMedicineAwardPreferenceContractTest extends TestCase
{
    #[Test]
    public function preferences_are_medicine_scoped_and_never_delete_original_awards(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineAwardPreferenceService.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_10_000000_create_pharma_medicine_award_preferences_table.php'));

        $this->assertStringContainsString("where('medicine_id', \$medicineId)", $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("'default', 'hide', 'restore'", $service);
        $this->assertStringContainsString("'source_record_type', 'award_price'", $service);
        $this->assertStringNotContainsString('->delete()', $service);
        $this->assertStringContainsString("unique(['medicine_id', 'origin', 'record_id']", $migration);
    }

    #[Test]
    public function product_ui_has_authorized_default_hide_and_restore_actions(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineAwardPreferenceController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/product-show.blade.php'));

        $this->assertStringContainsString('products.award-preferences.update', $routes);
        $this->assertStringContainsString("'client.pharma.products.edit'", $controller);
        $this->assertStringContainsString('@if($canManageAwards)', $view);
        $this->assertStringContainsString('Đặt mặc định', $view);
        $this->assertStringContainsString('Gỡ bỏ', $view);
        $this->assertStringContainsString('Khôi phục', $view);
    }

    #[Test]
    public function both_excel_exporters_consume_selected_award_read_model(): void
    {
        $related = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelRelatedDataService.php'));
        $client = file_get_contents(base_path('Modules/Pharma/Services/MedicineConfiguredExcelExporter.php'));
        $admin = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));

        $this->assertStringContainsString('defaultFor((int) $medicine->id)', $related);
        $this->assertStringContainsString("['winningName']", $related);
        $this->assertStringContainsString("['tenCdtBmt']", $related);
        $this->assertStringContainsString("['donGia']", $related);
        $this->assertStringContainsString("'allocated_quantity'", $related);
        $this->assertStringContainsString('$related->preview($medicine)', $client);
        $this->assertStringContainsString('$related->preview($medicine)', $admin);
    }
}
