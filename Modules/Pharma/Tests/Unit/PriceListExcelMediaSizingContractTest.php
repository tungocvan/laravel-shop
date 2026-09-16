<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PriceListExcelMediaSizingContractTest extends TestCase
{
    public function test_designer_exposes_profile_scoped_logo_and_signature_dimensions(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));
        $panel = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-media-dimensions.blade.php'));
        $wrapper = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v32.blade.php'));

        foreach (['logo_width_cm', 'logo_height_cm', 'signature_width_cm', 'signature_height_cm'] as $key) {
            $this->assertStringContainsString("headerFooter.{$key}", $panel);
            $this->assertStringContainsString("headerFooter.{$key}", $component);
        }

        foreach (['4.65', '2.82', '4.00', '3.60'] as $default) {
            $this->assertStringContainsString($default, $component);
        }

        $this->assertStringContainsString('Giữa 5 cột cuối', $panel);
        $this->assertStringContainsString('export-media-dimensions', $wrapper);
        $this->assertStringContainsString('export-configurator-v32', $component);
    }

    public function test_dimension_validation_matches_excel_layout_limits(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/ExportConfigurator.php'));

        $this->assertStringContainsString("'headerFooter.logo_width_cm'=>'nullable|numeric|min:1|max:12'", $component);
        $this->assertStringContainsString("'headerFooter.logo_height_cm'=>'nullable|numeric|min:1|max:8'", $component);
        $this->assertStringContainsString("'headerFooter.signature_width_cm'=>'nullable|numeric|min:1|max:12'", $component);
        $this->assertStringContainsString("'headerFooter.signature_height_cm'=>'nullable|numeric|min:1|max:8'", $component);
    }
}
