<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaTenderValidityContractTest extends TestCase
{
    #[Test]
    public function manual_validity_is_scoped_to_award_without_mutating_source(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineAwardPreferenceService.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_10_000001_add_tender_validity_to_pharma_medicine_award_preferences.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineAwardPreferenceController.php'));

        $this->assertStringContainsString("string('tender_validity_text', 255)->nullable()", $migration);
        $this->assertStringContainsString('function updateTenderValidity(', $service);
        $this->assertStringContainsString("'medicine_id' => \$medicineId, 'origin' => \$origin, 'record_id' => \$recordId", $service);
        $this->assertStringContainsString("where('source_record_type', 'award_price')", $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringNotContainsString('->delete()', $service);
        $this->assertStringContainsString("'client.pharma.products.edit'", $controller);
        $this->assertStringContainsString("'tender_validity_text' => ['nullable', 'string', 'max:255']", $controller);
        $this->assertStringContainsString("['default', 'hide', 'restore', 'validity']", $controller);
    }

    #[Test]
    public function product_ui_and_excel_share_selected_award_validity(): void
    {
        $catalog = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/product-show.blade.php'));
        $excel = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelRelatedDataService.php'));

        $this->assertStringContainsString("'tender_validity_text'] =", $catalog);
        $this->assertStringContainsString('Hiệu lực thời gian thầu', $view);
        $this->assertStringContainsString('data-validity-editor', $view);
        $this->assertStringContainsString('Nhập thủ công', $view);
        $this->assertStringContainsString("['contract_period_text'] = trim(\$validity)", $excel);
        $this->assertStringContainsString("['canonical:'.\$row->id]", $excel);
        $this->assertStringNotContainsString("['hanDung']", $excel);
    }
}
