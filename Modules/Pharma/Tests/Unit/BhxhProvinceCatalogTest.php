<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BhxhProvinceCatalogTest extends TestCase
{
    #[Test]
    public function catalog_maps_public_bhxh_codes_to_display_names(): void
    {
        $catalog = app(BhxhProvinceCatalog::class)->all();

        $this->assertSame('Thành phố Cần Thơ', $catalog['92TTT']);
        $this->assertSame('Thành phố Hồ Chí Minh', $catalog['79TTT']);
        $this->assertSame('Thành phố Hà Nội', $catalog['01TTT']);
    }

    #[Test]
    public function duplicate_display_names_keep_distinct_source_codes(): void
    {
        $catalog = app(BhxhProvinceCatalog::class)->all();

        $this->assertSame('Tỉnh An Giang', $catalog['89TTT']);
        $this->assertSame('Tỉnh An Giang', $catalog['91TTT']);
        $this->assertContains('89TTT', app(BhxhProvinceCatalog::class)->codes());
        $this->assertContains('91TTT', app(BhxhProvinceCatalog::class)->codes());
    }

    #[Test]
    public function bhxh_lookup_view_uses_a_province_select_not_a_manual_code_input(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('<select id="ma_tinh" name="ma_tinh"', $view);
        $this->assertStringContainsString('- Chọn Tỉnh/Thành -', $view);
        $this->assertStringNotContainsString('<input id="ma_tinh"', $view);
    }
}
