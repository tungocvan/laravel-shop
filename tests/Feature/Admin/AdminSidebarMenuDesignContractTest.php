<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminSidebarMenuDesignContractTest extends TestCase
{
    public function test_design_service_exposes_sidebar_menu_tokens(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));

        foreach (['--admin-sidebar-menu-font-family', '--admin-sidebar-menu-font-size', '--admin-sidebar-menu-title-color', '--admin-sidebar-menu-icon-color', '--admin-sidebar-menu-icon-size', '--admin-sidebar-menu-item-height', '--admin-sidebar-menu-padding-x', '--admin-sidebar-menu-padding-y', '--admin-sidebar-menu-content-gap', '--admin-sidebar-menu-item-gap', '--admin-sidebar-submenu-font-size', '--admin-sidebar-submenu-title-color', '--admin-sidebar-submenu-indent', '--admin-sidebar-submenu-padding-x', '--admin-sidebar-submenu-padding-y', '--admin-sidebar-submenu-offset', '--admin-sidebar-submenu-item-gap', '--admin-sidebar-menu-group-gap', '--admin-sidebar-active-title-color', '--admin-sidebar-active-icon-color'] as $token) {
            $this->assertStringContainsString($token, $service);
        }
    }

    public function test_theme_editor_manages_menu_item_submenu_spacing_and_active_state(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));

        $this->assertStringContainsString('Sidebar Menu Typography & States', $view);
        $this->assertStringContainsString('id="sidebar-menu"', $view);
        foreach (['config.design.sidebar_menu.item.font_family','config.design.sidebar_menu.item.title_color','config.design.sidebar_menu.item.icon_color','config.design.sidebar_menu.item.padding_x','config.design.sidebar_menu.item.padding_y','config.design.sidebar_menu.item.content_gap','config.design.sidebar_menu.item.item_gap','config.design.sidebar_menu.submenu.font_size','config.design.sidebar_menu.submenu.indent','config.design.sidebar_menu.submenu.padding_x','config.design.sidebar_menu.submenu.padding_y','config.design.sidebar_menu.submenu.offset','config.design.sidebar_menu.submenu.item_gap','config.design.sidebar_menu.group.gap','config.design.sidebar_menu.active.title_color'] as $model) {
            $this->assertStringContainsString($model, $view);
        }
        $this->assertStringContainsString("'config.design.sidebar_menu.item.font_family'=>'required|in:inherit,sans,serif,mono'", $component);
        $this->assertStringContainsString("'config.design.sidebar_menu.item.padding_x'=>'required|in:8,10,12,14,16'", $component);
        $this->assertStringContainsString("'config.design.sidebar_menu.group.gap'=>'required|in:2,4,6,8,12'", $component);
    }

    public function test_runtime_navigation_consumes_sidebar_menu_spacing_tokens(): void
    {
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));

        foreach (['var(--admin-sidebar-menu-item-height)','var(--admin-sidebar-menu-font-family)','var(--admin-sidebar-menu-icon-color)','var(--admin-sidebar-active-title-color)','var(--admin-sidebar-menu-padding-y)','var(--admin-sidebar-menu-padding-x)','var(--admin-sidebar-menu-content-gap)'] as $token) {
            $this->assertStringContainsString($token, $item);
        }
        foreach (['var(--admin-sidebar-submenu-font-family)','var(--admin-sidebar-submenu-indent)','var(--admin-sidebar-submenu-title-color)','var(--admin-sidebar-submenu-padding-y)','var(--admin-sidebar-submenu-padding-x)','var(--admin-sidebar-submenu-offset)','var(--admin-sidebar-submenu-item-gap)','var(--admin-sidebar-menu-group-gap)'] as $token) {
            $this->assertStringContainsString($token, $group);
        }
        $this->assertStringContainsString('var(--admin-sidebar-menu-item-gap)', $sidebar);
        $this->assertStringNotContainsString('gap-3 rounded-lg px-3 py-2', $item);
        $this->assertStringNotContainsString('space-y-0.5', $group);
    }

    public function test_default_theme_restores_professional_sidebar_menu_rhythm(): void
    {
        $profiles = file_get_contents(base_path('Modules/Admin/Services/AdminThemeProfileService.php'));
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        foreach (["'item_height'=>'44'","'padding_x'=>'12'","'padding_y'=>'8'","'content_gap'=>'12'","'item_gap'=>'4'","'indent'=>'28'","'offset'=>'12'","'group'=>['gap'=>'4']"] as $default) {
            $this->assertStringContainsString($default, $profiles);
        }
        foreach (["'item_height' => '44'","'padding_x' => '12'","'padding_y' => '8'","'content_gap' => '12'","'item_gap' => '4'","'indent' => '28'","'offset' => '12'","'group' => ['gap' => '4']"] as $default) {
            $this->assertStringContainsString($default, $config);
        }
        $this->assertStringContainsString('Restore Default sẽ trả toàn bộ nhóm này về bộ Professional Indigo tối ưu.', $view);
        $this->assertStringContainsString('14px / 500 / 44px', $view);
    }
}
