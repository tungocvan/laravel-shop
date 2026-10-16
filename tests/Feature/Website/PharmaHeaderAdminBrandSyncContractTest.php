<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaHeaderAdminBrandSyncContractTest extends TestCase
{
    public function test_pharma_header_reads_the_same_brand_settings_as_admin(): void
    {
        $admin = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/GeneralSettings.php'));
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString("'header.brand_name' => \$this->brand_name", $admin);
        $this->assertStringContainsString("\$values['header.brand_logo'] = \$newLogo", $admin);
        $this->assertStringContainsString("get('header.brand_name')", $header);
        $this->assertStringContainsString("get('header.brand_logo')", $header);
        $this->assertStringContainsString("get('site_logo')", $header);
        $this->assertStringContainsString("get('site_name', 'Dược phẩm')", $header);
    }

    public function test_desktop_and_mobile_render_the_configured_logo_and_accessible_brand(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertSame(2, substr_count($header, '@if($brandLogoUrl)'));
        $this->assertSame(2, substr_count($header, 'src="{{ $brandLogoUrl }}"'));
        $this->assertStringContainsString('aria-label="{{ $companyName }} - về trang chủ"', $header);
        $this->assertStringNotContainsString('aria-label="FlexBiz - về trang chủ"', $header);
        $this->assertStringContainsString('mb_substr($companyName, 0, 1)', $header);
        $this->assertStringContainsString('object-contain', $header);
    }
}
