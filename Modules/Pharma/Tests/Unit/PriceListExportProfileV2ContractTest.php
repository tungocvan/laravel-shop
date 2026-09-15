<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExportProfileV2ContractTest extends TestCase
{
    #[Test]
    public function export_configurator_supports_persistent_profiles_media_and_portable_json(): void
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
        $this->assertStringContainsString('Export cấu hình JSON', $view);
        $this->assertStringContainsString('Chọn file Import', $view);
        $this->assertStringContainsString('Nội dung & thương hiệu', $view);
        $this->assertStringContainsString('Cột dữ liệu', $view);
        $this->assertStringContainsString('Thiết lập trang in', $view);
    }

    #[Test]
    public function excel_export_applies_saved_profile_and_embeds_brand_media(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));

        $this->assertStringContainsString('PriceListExportProfileService $profiles', $controller);
        $this->assertStringContainsString("'export_profile_id'", $controller);
        $this->assertStringContainsString('new Drawing()', $controller);
        $this->assertStringContainsString("$profile['logo_path']", $controller);
        $this->assertStringContainsString("$profile['signature_path']", $controller);
        $this->assertStringContainsString('setPaperSize', $controller);
        $this->assertStringContainsString('setFitToWidth', $controller);
    }
}
