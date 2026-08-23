<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminLayoutSettingsHubContractTest extends TestCase
{
    public function test_existing_admin_layout_route_name_is_preserved_and_subroutes_are_explicit(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringContainsString("->name('layout');", $routes);

        foreach (['general', 'header', 'sidebar', 'footer', 'design', 'navigation'] as $section) {
            $this->assertStringContainsString("/{$section}", $routes);
            $this->assertStringContainsString("->name('{$section}')", $routes);
        }
    }

    public function test_layout_overview_is_a_dashboard_not_the_long_settings_form(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-dashboard.blade.php'));

        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-dashboard')", $page);
        $this->assertStringNotContainsString("admin-layout-config", $page);
        $this->assertStringContainsString('Tổng quan giao diện Admin', $dashboard);
        $this->assertStringContainsString('Thiết lập', $dashboard);
    }

    public function test_dashboard_service_exposes_six_quick_access_areas(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminLayoutDashboardService.php'));

        foreach (['general', 'header', 'sidebar', 'footer', 'design', 'navigation'] as $section) {
            $this->assertStringContainsString("'key' => '{$section}'", $service);
            $this->assertStringContainsString("'route' => 'admin.layout.{$section}'", $service);
        }
    }

    public function test_section_editor_preserves_other_configuration_when_saving_or_resetting(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertStringContainsString('array_replace_recursive($manager->config(), $validated)', $component);
        $this->assertStringContainsString('$this->sectionPayload($manager->defaults())', $component);
        $this->assertStringContainsString('resetSection', $component);
        $this->assertStringNotContainsString('$manager->reset();', $component);
    }

    public function test_section_page_has_back_navigation_to_the_layout_hub(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));

        $this->assertStringContainsString("route('admin.layout')", $page);
        $this->assertStringContainsString("['section' => \$section]", $page);
        $this->assertStringContainsString('Tổng quan giao diện Admin', $page);
    }
}
