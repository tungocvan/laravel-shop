<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteShellControlsConfigurationTest extends TestCase
{
    public function test_website_settings_exposes_shell_visibility_and_maintenance_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteShellService.php'));

        $this->assertStringContainsString("'layout'=>'Bố cục Website'", $view);
        $this->assertStringContainsString('wire:model="shell.{{ $key }}"', $view);

        foreach (['header_enabled', 'homepage_enabled', 'footer_enabled'] as $key) {
            $this->assertStringContainsString("['{$key}'", $view);
            $this->assertStringContainsString("'{$key}'", $service);
        }

        $this->assertStringContainsString('shell.maintenance.enabled', $view);
        $this->assertStringContainsString('shell.maintenance.title', $view);
        $this->assertStringContainsString('shell.maintenance.message', $view);
        $this->assertStringContainsString("'website.shell' => \$this->shell", $component);
        $this->assertStringContainsString('WebsiteShellService $shellService', $component);
        $this->assertStringContainsString('strip_tags($value)', $service);
    }

    public function test_frontend_shell_honors_header_homepage_footer_and_maintenance_contract(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $maintenance = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/maintenance.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString("'header_enabled'", $layout);
        $this->assertStringContainsString("'homepage_enabled'", $layout);
        $this->assertStringContainsString("'footer_enabled'", $layout);
        $this->assertStringContainsString("'maintenance.enabled'", $layout);
        $this->assertStringContainsString("request()->routeIs('home')", $layout);
        $this->assertStringContainsString('Website::partials.layout.maintenance', $layout);
        $this->assertStringContainsString("get('website.shell')", $provider);
        $this->assertStringContainsString('WebsiteShellService::class', $provider);
        $this->assertStringContainsString('maintenance.title', $maintenance);
        $this->assertStringContainsString('maintenance.message', $maintenance);
        $this->assertStringNotContainsString('{!!', $maintenance);
    }

    public function test_website_dashboard_has_settings_quick_access_alongside_layout_managers(): void
    {
        $dashboard = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/dashboard/website-dashboard.blade.php'));

        $this->assertStringContainsString('Cài đặt Website', $dashboard);
        $this->assertStringContainsString('admin.website.settings', $dashboard);
        $this->assertStringContainsString('admin.home.settings', $dashboard);
        $this->assertStringContainsString('admin.header.settings', $dashboard);
        $this->assertStringContainsString('admin.footer.settings', $dashboard);
    }
}
