<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExportProfileContractTest extends TestCase
{
    #[Test]
    public function export_profiles_are_persistent_and_cover_full_price_list_layout(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_190000_create_price_list_export_profiles_table.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $layout = file_get_contents(base_path('Modules/Pharma/Services/PriceListExcelDocumentLayout.php'));

        $this->assertStringContainsString("Schema::create('pharma_price_list_export_profiles'", $migration);
        $this->assertStringContainsString('header_footer', $migration);
        $this->assertStringContainsString('page_setup', $migration);
        $this->assertStringContainsString("'bid_price'", $service);
        $this->assertStringContainsString("'actual_receivable_price'", $service);
        $this->assertStringContainsString('Thương hiệu & nội dung', $view);
        $this->assertStringContainsString('Cột dữ liệu', $view);
        $this->assertStringContainsString('Trang in', $view);
        $this->assertStringContainsString('export_profile_id', $controller);
        $this->assertStringContainsString('setFitToWidth', $controller);
        $this->assertStringContainsString('setHorizontalCentered', $controller);
        $this->assertStringContainsString("\$dateText = trim((string) (\$hf['footer_year'] ?? ''));", $layout);
        $this->assertStringNotContainsString("'ngày.....tháng.....năm '.\$year", $layout);
    }
}
