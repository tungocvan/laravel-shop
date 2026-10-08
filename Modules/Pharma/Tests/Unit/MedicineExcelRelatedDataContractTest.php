<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineExcelRelatedDataService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineExcelRelatedDataContractTest extends TestCase
{
    #[Test]
    public function related_fields_are_available_as_nullable_preview_but_not_export_columns(): void
    {
        $service = new MedicineExcelRelatedDataService();
        $values = $service->emptyValues();

        foreach ([
            'cost_price', 'supplier_name', 'hssp_status', 'winning_company_name',
            'investor_name', 'decision_number', 'decision_date',
            'contract_period_text', 'winning_price', 'allocated_quantity',
        ] as $key) {
            $this->assertArrayHasKey($key, $values);
            $this->assertSame('', $values[$key]);
            $this->assertArrayNotHasKey($key, MedicineExcelProfileService::COLUMNS);
        }

        $this->assertSame($values, $service->preview(null));
    }

    #[Test]
    public function preview_ui_is_read_only_and_missing_values_are_blank(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelRelatedDataService.php'));

        $this->assertStringContainsString('Dữ liệu liên kết · Chỉ xem trước', $view);
        $this->assertStringContainsString("relatedValues[\$fieldKey] ?? ''", $view);
        $this->assertStringContainsString('wire:click="previewRelatedData"', $view);
        $this->assertStringContainsString('function previewRelatedData(', $component);
        $this->assertStringContainsString("currentProfile()->first()", $resolver);
        $this->assertStringContainsString("drugBidAwards()->where('is_active', true)", $resolver);
    }
}
