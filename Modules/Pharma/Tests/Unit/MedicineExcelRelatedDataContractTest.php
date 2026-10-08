<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineExcelRelatedDataService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineExcelRelatedDataContractTest extends TestCase
{
    #[Test]
    public function related_fields_are_optional_export_columns_and_missing_values_are_blank(): void
    {
        $related = new MedicineExcelRelatedDataService();
        $defaults = (new MedicineExcelProfileService())->defaults();

        foreach ($related->emptyValues() as $key => $value) {
            $this->assertSame('', $value);
            $this->assertArrayHasKey($key, MedicineExcelProfileService::COLUMNS);
            $this->assertNotContains($key, $defaults['columns']);
        }
        $this->assertSame($related->emptyValues(), $related->preview(null));
    }

    #[Test]
    public function related_fields_can_be_selected_and_exported_without_preview_input(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));

        $this->assertStringContainsString('relatedGroups as $groupKey', $view);
        $this->assertStringContainsString('x-on:click="add(@js($key))"', $view);
        $this->assertStringNotContainsString('previewMedicineCode', $component);
        $this->assertStringContainsString('array_merge($values, $related->preview($medicine))', $controller);
        $this->assertStringContainsString('($value ?? \'\')', $controller);
    }
}
