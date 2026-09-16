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
    public function media_removal_is_only_committed_when_profile_is_saved(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $compact = str_replace(' ', '', $component);

        $this->assertStringContainsString('public bool $removeLogoRequested = false;', $component);
        $this->assertStringContainsString('public bool $removeSignatureRequested = false;', $component);
        $this->assertStringContainsString('public ?string $originalLogoPath = null;', $component);
        $this->assertStringContainsString('$this->removeLogoRequested=true', $compact);
        $this->assertStringContainsString('$this->removeSignatureRequested=true', $compact);
        $this->assertStringContainsString("\$this->originalLogoPath!==\$saved['logo_path']", $compact);
        $this->assertStringContainsString("\$this->originalSignaturePath!==\$saved['signature_path']", $compact);
        $this->assertStringNotContainsString('removeLogo():void{$this->deleteMedia(', $compact);
        $this->assertStringNotContainsString('removeSignature():void{$this->deleteMedia(', $compact);
    }

    #[Test]
    public function column_designer_uses_data_library_export_order_and_live_preview(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));
        $compact = str_replace(' ', '', $component);

        $this->assertStringContainsString('public function addColumn(string $key)', $component);
        $this->assertStringContainsString('public function removeColumn(string $key)', $component);
        $this->assertStringContainsString('public function moveColumn(string $key, int $offset)', $component);
        $this->assertStringContainsString('public function resetSelectedOrder(): void', $component);
        $this->assertStringContainsString('private function replaceSelectedOrder(array $selected): void', $component);
        $this->assertStringContainsString("'column_order'=>\$this->columnOrder", $compact);
        $this->assertStringContainsString("'column_groups'=>\$canonicalGroups", $compact);

        $this->assertStringContainsString('1. Kho dữ liệu', $view);
        $this->assertStringContainsString('2. Cột sẽ xuất Excel', $view);
        $this->assertStringContainsString('3. Xem trước header Excel', $view);
        $this->assertStringContainsString('A/B/C ở đây chính là vị trí thật trong file.', $view);
        $this->assertStringContainsString("wire:click=\"addColumn('{{ \$key }}')\"", $view);
        $this->assertStringContainsString("wire:click=\"removeColumn('{{ \$key }}')\"", $view);
        $this->assertStringContainsString("wire:click=\"moveColumn('{{ \$key }}',-1)\"", $view);
        $this->assertStringContainsString("wire:click=\"moveColumn('{{ \$key }}',1)\"", $view);
        $this->assertStringContainsString('wire:click="resetSelectedOrder"', $view);
        $this->assertStringContainsString('wire:key="export-column-{{ $key }}"', $view);
        $this->assertStringContainsString('wire:key="column-inspector-{{ $activeColumnKey }}"', $view);
        $this->assertStringContainsString('wire:key="column-header-{{ $activeColumnKey }}"', $view);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="headers.{{ $activeColumnKey }}"', $view);
        $this->assertStringNotContainsString('Vị trí trên Excel<select', $view);
        $this->assertStringNotContainsString('Cross-group Drag & Drop', $view);
        $this->assertStringNotContainsString('draggable="true"', $view);
    }

    #[Test]
    public function restoring_a_column_resets_position_and_all_column_level_customization(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $compact = str_replace(' ', '', $component);

        $this->assertStringContainsString('array_keys(PriceListExportProfileService::COLUMNS)', $component);
        $this->assertStringContainsString('$this->setColumnPosition($key,', $component);
        $this->assertStringContainsString("\$this->headers[\$key]=\$definition['label'];", $compact);
        $this->assertStringContainsString("\$this->alignments[\$key]=\$definition['align'];", $compact);
        $this->assertStringContainsString("\$this->widths[\$key]=\$definition['width'];", $compact);
        $this->assertStringContainsString("\$this->dataTypes[\$key]=\$definition['type'];", $compact);
        $this->assertStringContainsString('$this->decimals[$key]=0;', $compact);
        $this->assertStringContainsString('in_array($key,PriceListExportProfileService::DEFAULT_SELECTED,true)', $compact);
    }

    #[Test]
    public function pharma_dashboard_promotes_database_price_list_workspace(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PharmaDashboardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString('use Modules\\Pharma\\Models\\PriceList;', $service);
        $this->assertStringContainsString("'price_lists' => \$this->count(PriceList::class, 'price_lists')", $service);
        $this->assertStringContainsString('private function priceListSummary(): array', $service);
        $this->assertStringContainsString("route('admin.pharma.price-lists.index')", $view);
        $this->assertStringContainsString('Trung tâm Bảng giá Pharma', $view);
        $this->assertStringContainsString('Price List v2', $view);
        $this->assertStringNotContainsString('Workbook bảng giá', $view);
        $this->assertStringNotContainsString('Thiếu file nguồn', $view);
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
        $this->assertStringContainsString('catch(Throwable)', str_replace(' ', '', $controller));
        $this->assertStringContainsString("setCellValueExplicit(\$coordinate,(string)\$value,DataType::TYPE_STRING)", str_replace(' ', '', $controller));
    }
}
