<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminSidebarMenuDesignContractTest extends TestCase
{
    public function test_design_service_exposes_sidebar_menu_tokens(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));

        foreach (['--admin-sidebar-menu-font-family', '--admin-sidebar-menu-font-size', '--admin-sidebar-menu-title-color', '--admin-sidebar-menu-icon-color', '--admin-sidebar-menu-icon-size', '--admin-sidebar-menu-item-height', '--admin-sidebar-submenu-font-size', '--admin-sidebar-submenu-title-color', '--admin-sidebar-submenu-indent', '--admin-sidebar-active-title-color', '--admin-sidebar-active-icon-color'] as $token) {
            $this->assertStringContainsString($token, $service);
        }
    }

    public function test_theme_editor_manages_menu_item_submenu_and_active_state(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));

        $this->assertStringContainsString('Sidebar Menu Typography & States', $view);
        $this->assertStringContainsString('id="sidebar-menu"', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.item.font_family', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.item.title_color', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.item.icon_color', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.submenu.font_size', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.submenu.indent', $view);
        $this->assertStringContainsString('config.design.sidebar_menu.active.title_color', $view);
        $this->assertStringContainsString("'config.design.sidebar_menu.item.font_family'=>'required|in:inherit,sans,serif,mono'", $component);
    }

    public function test_runtime_navigation_consumes_sidebar_menu_tokens(): void
    {
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));

        $this->assertStringContainsString('var(--admin-sidebar-menu-item-height)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-font-family)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-icon-color)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-active-title-color)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-font-family)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-indent)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-title-color)', $group);
    }

    public function test_theme_profiles_include_sidebar_menu_presentation(): void
    {
        $profiles = file_get_contents(base_path('Modules/Admin/Services/AdminThemeProfileService.php'));
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));

        $this->assertStringContainsString("'sidebar_menu'=>[", $profiles);
        $this->assertStringContainsString("'sidebar_menu' => [", $config);
        $this->assertStringContainsString("'item_height' => '44'", $config);
        $this->assertStringContainsString("'indent' => '28'", $config);
    }
}
