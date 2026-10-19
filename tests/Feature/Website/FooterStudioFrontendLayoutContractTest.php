<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioFrontendLayoutContractTest extends TestCase
{
    public function test_profile_grid_keeps_app_install_readable_and_uses_profile_widths(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer-shared.blade.php'));
        $this->assertStringContainsString('desktopProfileColumns', $footer);
        $this->assertStringContainsString("=== 'app-install' ? max(4,", $footer);
        $this->assertStringContainsString('grid-template-columns:', $footer);
        $this->assertStringContainsString('minmax(0, ', $footer);
        $this->assertStringContainsString('md:hidden space-y-7', $footer);
    }

    public function test_brand_profile_includes_contact_and_empty_menus_can_inherit_legacy_links(): void
    {
        $column = file_get_contents(base_path('Modules/Website/resources/views/components/footer/profile-column.blade.php'));
        $resolver = file_get_contents(base_path('Modules/Website/Services/FooterProfileResolver.php'));
        $this->assertStringContainsString("components.footer.brand", $column);
        $this->assertStringContainsString("components.footer.contact", $column);
        $this->assertStringContainsString('$legacyMenuColumns', $resolver);
        $this->assertStringContainsString('$links->isEmpty()', $resolver);
        $this->assertStringContainsString("'profile_columns_active' => false", $resolver);
    }
}
