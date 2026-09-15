<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExportProfileV2ContractTest extends TestCase
{
    #[Test]
    public function export_configurator_supports_persistent_profiles_media_preview_and_portable_json(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        $this->assertStringContainsString('use WithFileUploads;', $component);
        $this->assertStringContainsString('logoUpload', $component);
        $this->assertStringContainsString('signatureUpload', $component);
        $this->assertStringContainsString('exportJson()', $component);
        $this->assertStringContainsString('importJson()', $component);
        $this->assertStringContainsString('pharma.price-list-export-profile.v1', $service);
        $this->assertStringContainsString('Export JSON', $view);
        $this->assertStringContainsString('Import JSON', $view);
        $this->assertStringContainsString('temporaryUrl()', $view);
        $this->assertStringContainsString("Storage::disk('public')->url(\$logoPath)", $view);
        $this->assertStringContainsString("Storage::disk('public')->url(\$signaturePath)", $view);
        $this->assertStringContainsString('Thương hiệu & nội dung', $view);
        $this->assertStringContainsString('Cột dữ liệu', $view);
        $this->assertStringContainsString('Trang in', $view);
    }

    #[Test]
    public function column_designer_supports_drag_drop_search_filters_groups_and_inspector(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        $this->assertStringContainsString('public function reorderColumns(array $orderedKeys)', $component);
        $this->assertStringContainsString('public function selectGroup(string $group, bool $selected = true)', $component);
        $this->assertStringContainsString("'columnGroups'=>PriceListExportProfileService::GROUPS", str_replace(' ', '', $component));
        $this->assertStringContainsString('Column Designer', $view);
        $this->assertStringContainsString('Column Inspector', $view);
        $this->assertStringContainsString('draggable="true"', $view);
        $this->assertStringContainsString('@drop.prevent="dropOn(', $view);
        $this->assertStringContainsString('$wire.reorderColumns(order)', $view);
        $this->assertStringContainsString('Tìm tên cột hoặc field', $view);
        $this->assertStringContainsString('Đang xuất', $view);
        $this->assertStringContainsString('Chưa xuất', $view);
        $this->assertStringContainsString('Chọn nhóm', $view);
        $this->assertStringContainsString('Bỏ nhóm', $view);
        $this->assertStringNotContainsString('moveUp(', $component);
        $this->assertStringNotContainsString('moveDown(', $component);
    }

    #[Test]
    public function excel_export_applies_saved_profile_media_page_setup_and_cell_types(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));

        $this->assertStringContainsString('PriceListExportProfileService $profiles', $controller);
        $this->assertStringContainsString("'export_profile_id'", $controller);
        $this->assertStringContainsString('new Drawing()', $controller);
        $this->assertStringContainsString('$profile[\'logo_path\']', $controller);
        $this->assertStringContainsString('$profile[\'signature_path\']', $controller);
        $this->assertStringContainsString('setPaperSize', $controller);
        $this->assertStringContainsString('setFitToWidth', $controller);
        $this->assertStringContainsString('writeConfiguredCell(', $controller);
        $this->assertStringContainsString('DataType::TYPE_STRING', $controller);
        $this->assertStringContainsString('Date::PHPToExcel', $controller);
        $this->assertStringContainsString("setFormatCode('dd/mm/yyyy')", $controller);
        $this->assertStringContainsString('getNumberFormat()->setFormatCode', $controller);
    }
}
