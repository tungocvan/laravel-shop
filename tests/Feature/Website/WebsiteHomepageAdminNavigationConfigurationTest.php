<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageAdminNavigationConfigurationTest extends TestCase
{
    public function test_external_homepage_section_editors_preserve_return_context(): void
    {
        $registry = file_get_contents(base_path('Modules/Website/Services/HomepageSectionRegistry.php'));
        $banner = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/banner/index.blade.php'));
        $flashSale = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/flash-sale/index.blade.php'));

        $this->assertStringContainsString("route(\$routeName, ['from' => 'homepage'])", $registry);

        foreach ([$banner, $flashSale] as $view) {
            $this->assertStringContainsString("request('from') === 'homepage'", $view);
            $this->assertStringContainsString("route('admin.home.settings')", $view);
            $this->assertStringContainsString('← Quay lại bố cục', $view);
        }
    }

    public function test_website_dashboard_quick_access_exposes_homepage_header_and_footer(): void
    {
        $dashboard = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/dashboard/website-dashboard.blade.php'));

        $this->assertStringContainsString("['Homepage',", $dashboard);
        $this->assertStringContainsString("'admin.home.settings'", $dashboard);
        $this->assertStringContainsString("['Header',", $dashboard);
        $this->assertStringContainsString("'admin.header.settings'", $dashboard);
        $this->assertStringContainsString("['Footer',", $dashboard);
        $this->assertStringContainsString("'admin.footer.settings'", $dashboard);
    }
}
