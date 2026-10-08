<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineExcelProfileService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineExcelProfileContractTest extends TestCase
{
    #[Test]
    public function medicine_excel_profile_has_independent_schema_and_product_type(): void
    {
        $service = new MedicineExcelProfileService();
        $defaults = $service->defaults();

        $this->assertContains('product_type', $defaults['columns']);
        $this->assertSame('Loại sản phẩm', MedicineExcelProfileService::COLUMNS['product_type']);
        $this->assertContains('medicine_code', $defaults['columns']);
        $this->assertContains('sku', $defaults['columns']);
        $this->assertSame('A4', $defaults['settings']['paper_size']);
        $this->assertSame('landscape', $defaults['settings']['orientation']);
        $this->assertSame('pharma_medicine_excel_profiles', (new \Modules\Pharma\Models\MedicineExcelProfile())->getTable());
    }

    #[Test]
    public function configured_export_is_separate_from_legacy_import_export(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $catalog = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));

        $this->assertStringContainsString("name('export-configured')", $routes);
        $this->assertStringContainsString("name('export')", $routes);
        $this->assertStringContainsString("name('import.index')", $routes);
        $this->assertStringContainsString("route('admin.pharma.medicines.export')", $catalog);
        $this->assertStringContainsString("route('admin.pharma.medicines.export-configured'", $catalog);
        $this->assertStringContainsString("pharma.medicine.excel-configurator", $catalog);
        $this->assertStringContainsString("'product_type' => Medicine::productTypeOptions()", $controller);
        $this->assertStringContainsString("->lazy(200)", $controller);
    }

    #[Test]
    public function configurator_supports_saved_profiles_and_custom_column_order(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        foreach (['openConfig', 'newProfile', 'move', 'selectAll', 'clearAll', 'save', 'deleteProfile'] as $method) {
            $this->assertStringContainsString('function '.$method.'(', $component);
        }
        $this->assertStringContainsString('wire:click="save"', $view);
        $this->assertStringContainsString('wire:model="settings.paper_size"', $view);
        $this->assertStringContainsString('wire:model="settings.orientation"', $view);
    }
}
