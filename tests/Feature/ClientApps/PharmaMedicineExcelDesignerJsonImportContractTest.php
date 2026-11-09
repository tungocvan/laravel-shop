<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaMedicineExcelDesignerJsonImportContractTest extends TestCase
{
    public function test_excel_designer_exposes_confirmed_json_import_only_to_clientportal(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        $this->assertStringContainsString("if(\$ownerGuard === 'web')", $view);
        $this->assertStringContainsString("route('client.pharma.products.excel-config.import-json')", $view);
        $this->assertStringContainsString('Nhập JSON', $view);
        $this->assertStringContainsString('Xuất JSON', $view);
        $this->assertStringContainsString('Xem trước cấu hình JSON', $view);
        $this->assertStringContainsString('Xác nhận nhập JSON', $view);
        $this->assertStringContainsString('schema_version !== 1', $view);
        $this->assertStringContainsString('file.size > 524288', $view);
        $this->assertStringContainsString('x-bind:disabled="!preview || busy"', $view);
        $this->assertStringContainsString('wire:ignore', $view);
    }

    public function test_existing_server_import_validates_and_creates_separate_profile(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelProfileController.php'));
        $this->assertStringContainsString('public function importJson(', $controller);
        $this->assertStringContainsString("'schema_version'", $controller);
        $this->assertStringContainsString('client.pharma.products.supplier-pricing', $controller);
        $this->assertStringContainsString("\$data['is_default'] = false", $controller);
        $this->assertStringContainsString("\$profiles->save(\$id, \$data, null, 'web')", $controller);
    }
}
