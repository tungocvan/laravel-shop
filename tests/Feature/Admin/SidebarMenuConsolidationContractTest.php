<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class SidebarMenuConsolidationContractTest extends TestCase
{
    public function test_sidebar_settings_reuses_the_existing_menu_designer_and_theme_editor_links_to_it(): void
    {
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));
        $theme = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        $this->assertStringContainsString("@include('Admin::livewire.settings.partials.sidebar-menu-designer')", $sidebar);
        $this->assertStringNotContainsString("@include('Admin::livewire.settings.partials.sidebar-menu-designer')", $theme);
        $this->assertStringContainsString('/admin/layout/sidebar', $theme);
        $this->assertStringContainsString('#sidebar-menu', $theme);
    }

    public function test_sidebar_save_and_reset_include_existing_design_menu_without_replacing_other_design_sections(): void
    {
        $source = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertStringContainsString("'design' => ['sidebar_menu' => data_get(", $source);
        $this->assertStringContainsString("'design.sidebar_menu.'.\$area", $source);
        $this->assertStringContainsString('isMenuColorReference', $source);
        $this->assertStringContainsString('array_replace_recursive($manager->config(), $validated)', $source);
    }
    public function test_sidebar_studio_has_six_tabs_and_preserves_single_save_form(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));

        foreach (['overview', 'colors', 'menu', 'layout', 'header', 'footer'] as $tab) {
            $this->assertStringContainsString("'".$tab."' =>", $view);
            $this->assertStringContainsString("activeTab === '".$tab."'", $view);
        }

        $this->assertSame(1, substr_count($view, 'wire:submit="save"'));
        $this->assertStringContainsString('id="sidebar-menu"', $view);
        $this->assertStringContainsString("window.history.replaceState", $view);
    }

    public function test_visual_state_presets_are_shared_with_sidebar_menu_and_persisted(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        foreach (['FlexBiz Emerald', 'Corporate Slate', 'Minimal Light'] as $name) {
            $this->assertStringContainsString($name, $component);
        }
        $this->assertStringContainsString('applySidebarVisualPreset', $view);
        $this->assertStringContainsString('saveSidebarVisualPreset', $view);
        $this->assertStringContainsString('sidebar-preset-applied', $view);
        $this->assertStringContainsString('normalizeSidebarVisualPresets', $manager);
        $this->assertStringContainsString("'visual_presets' =>", $manager);
    }

}
