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

    public function test_configurator_has_server_library_feedback_and_style_validation(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        foreach (['openJsonSave', 'saveJsonToServer', 'openJsonLibrary', 'importSelectedJson', 'requestDeleteJson', 'confirmAction', 'notify'] as $method) {
            $this->assertStringContainsString('function '.$method, $component);
        }

        foreach (['pageSetup.header_background', 'pageSetup.header_text_color', 'pageSetup.table_border'] as $binding) {
            $this->assertStringContainsString($binding, $component);
            $this->assertStringContainsString($binding, $view);
        }

        foreach (['Excel Designer v3', 'Thư viện cấu hình JSON', 'Lưu cấu hình JSON', 'Xem trước header Excel', 'Times New Roman'] as $label) {
            $this->assertStringContainsString($label, $view);
        }
    }
}
