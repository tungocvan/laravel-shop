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
    public function duplicate_display_names_are_hidden_from_ui_but_aliases_are_preserved(): void
    {
        $service = app(BhxhProvinceCatalog::class);
        $catalog = $service->all();
        $aliases = $service->aliases();

        $this->assertSame(1, count(array_filter($catalog, fn ($name) => $name === 'Tỉnh An Giang')));
        $this->assertSame(['89TTT', '91TTT'], $aliases['Tỉnh An Giang']);
        $this->assertContains('89TTT', $service->codes());
        $this->assertNotContains('91TTT', $service->codes());
    }

    #[Test]
    public function bhxh_lookup_view_uses_province_and_district_selects(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('<select id="ma_tinh" name="ma_tinh"', $view);
        $this->assertStringContainsString('- Chọn Tỉnh/Thành -', $view);
        $this->assertStringContainsString('<select id="ma_quan_huyen" name="ma_quan_huyen"', $view);
        $this->assertStringContainsString('-- Toàn tỉnh --', $view);
        $this->assertStringNotContainsString('<input id="ma_tinh"', $view);
        $this->assertStringNotContainsString('<input id="ma_quan_huyen"', $view);
    }
}
