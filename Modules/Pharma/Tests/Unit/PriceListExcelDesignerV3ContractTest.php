<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PriceListExcelDesignerV3ContractTest extends TestCase
{
    public function test_excel_typography_supports_table_style_and_times_new_roman(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExcelTypography.php'));
        $this->assertStringContainsString("FONT_FAMILY = 'Times New Roman'", $service);
        $this->assertStringContainsString('BORDER_THIN', $service);
        $this->assertStringContainsString('header_background', $service);
        $this->assertStringContainsString('header_text_color', $service);
        $this->assertStringContainsString('getDefaultStyle()', $service);
    }

    public function test_json_library_is_private_per_admin_and_sanitizes_names(): void
    {
        $library = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportJsonLibrary.php'));
        $this->assertStringContainsString("Storage::disk('local')", $library);
        $this->assertStringContainsString("self::DIRECTORY.'/'.\$userId", $library);
        $this->assertStringContainsString('basename(', $library);
        $this->assertStringContainsString("'.json'", $library);
    }

    public function test_configurator_has_server_library_feedback_and_v32_workspace(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v31.blade.php'));
        $wrapper = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v32.blade.php'));

        foreach (['openJsonSave', 'saveJsonToServer', 'openJsonLibrary', 'importSelectedJson', 'requestDeleteJson', 'executeConfirmedAction', 'notify'] as $method) {
            $this->assertStringContainsString('function '.$method, $component);
        }
        $this->assertStringContainsString('function openConfirmation', $component);
        $this->assertStringNotContainsString('function confirmAction', $component);
        foreach (['pageSetup.header_background', 'pageSetup.header_text_color', 'pageSetup.table_border'] as $binding) {
            $this->assertStringContainsString($binding, $component);
            $this->assertStringContainsString($binding, $view);
        }
        foreach (['Excel Designer v3.1', 'Thư viện cấu hình JSON', 'Lưu cấu hình JSON', 'Xem trước header Excel', 'Times New Roman'] as $label) {
            $this->assertStringContainsString($label, $view);
        }
        $this->assertStringContainsString('export-configurator-v31', $wrapper);
        $this->assertStringContainsString('export-media-dimensions', $wrapper);
        $this->assertStringContainsString('price-list.export-configurator-v32', $component);
    }

    public function test_column_inspector_uses_isolated_draft_state_and_keyed_dom(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $compact = $this->compact($component);
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v31.blade.php'));

        $this->assertStringContainsString($this->compact('public array $columnDraft=[];'), $compact);
        $this->assertStringContainsString('function loadColumnDraft', $component);
        $this->assertStringContainsString('function commitColumnDraft', $component);
        $this->assertStringContainsString($this->compact('$key!==$this->activeColumnKey'), $compact);
        foreach (['$this->widths[$key]=', '$this->headers[$key]=', '$this->alignments[$key]=', '$this->dataTypes[$key]=', '$this->decimals[$key]='] as $assignment) {
            $this->assertStringContainsString($this->compact($assignment), $compact);
        }
        foreach (['wire:key="inspector-{{ $activeColumnKey }}"', 'wire:model="columnDraft.width"', 'wire:model="columnDraft.header"', 'wire:model="columnDraft.alignment"', 'wire:model="columnDraft.data_type"', 'wire:model="columnDraft.decimals"'] as $binding) {
            $this->assertStringContainsString($binding, $view);
        }
        $this->assertStringNotContainsString('wire:model="widths.{{ $activeColumnKey }}"', $view);
        $this->assertStringContainsString('Width phản ánh tương đối', $view);
    }

    private function compact(string $source): string
    {
        return preg_replace('/\s+/', '', $source) ?? $source;
    }
}
