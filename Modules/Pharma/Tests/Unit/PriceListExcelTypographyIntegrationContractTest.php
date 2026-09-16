<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExcelTypographyIntegrationContractTest extends TestCase
{
    #[Test]
    public function export_designer_exposes_product_font_size_from_ten_to_thirteen(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        $this->assertStringContainsString('Excel Typography', $view);
        $this->assertStringContainsString('Times New Roman', $view);
        $this->assertStringContainsString('12pt · Đậm · Canh giữa', $view);
        $this->assertStringContainsString('wire:model="pageSetup.product_font_size"', $view);
        $this->assertStringContainsString('<option value="10">10 pt</option>', $view);
        $this->assertStringContainsString('<option value="11">11 pt</option>', $view);
        $this->assertStringContainsString('<option value="12">12 pt · Mặc định</option>', $view);
        $this->assertStringContainsString('<option value="13">13 pt</option>', $view);
    }

    #[Test]
    public function price_list_export_applies_typography_after_writing_product_rows(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $compact = str_replace(' ', '', $controller);

        $this->assertStringContainsString('use Modules\\Pharma\\Services\\PriceListExcelTypography;', $controller);
        $this->assertStringContainsString('PriceListExcelTypography $typography', $controller);
        $this->assertStringContainsString('$typography->apply($sheet,$headerRow,$headerRow+$items->count(),$page);', $compact);
    }
}
