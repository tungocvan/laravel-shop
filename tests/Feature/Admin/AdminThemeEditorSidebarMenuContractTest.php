<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminThemeEditorSidebarMenuContractTest extends TestCase
{
    public function test_theme_editor_accepts_current_sidebar_visual_modes_and_not_legacy_modes(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));

        $this->assertStringContainsString("config.sidebar.presentation.background'=>'required|in:theme,light,dark,custom'", $component);
        $this->assertStringContainsString("'system', 'white' => 'light'", $component);
        $this->assertStringNotContainsString("data_set(\$this->config, 'sidebar.presentation.background', 'system')", $component);
    }

    public function test_theme_editor_uses_professional_sidebar_menu_designer_with_custom_color_picker(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));
        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));
        $colorControl = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/menu-color-control.blade.php'));

        $this->assertStringContainsString("@include('Admin::livewire.settings.partials.sidebar-menu-designer')", $view);
        $this->assertStringContainsString('Sidebar Menu Designer', $designer);
        $this->assertStringContainsString('Menu Item / Menu cha', $designer);
        $this->assertStringContainsString('SubMenu Item', $designer);
        $this->assertStringContainsString('Normal', $designer);
        $this->assertStringContainsString('Hover', $designer);
        $this->assertStringContainsString('Active', $designer);
        $this->assertStringContainsString('type="color"', $colorControl);
        $this->assertStringContainsString('preset hoặc #RRGGBB', $colorControl);
        $this->assertStringContainsString('wire:model.live="{{ $model }}"', $colorControl);
    }

    public function test_menu_item_hover_and_active_visual_state_contract_is_declared(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));
        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));

        foreach ([
            'background_mode',
            'background_color',
            'hover_background_mode',
            'hover_background_color',
            'hover_title_color',
            'hover_icon_color',
        ] as $token) {
            $this->assertStringContainsString("'{$token}'", $config);
            $this->assertStringContainsString("item.{$token}", $component);
        }

        $this->assertStringContainsString('menu_background_mode', $config);
        $this->assertStringContainsString('menu_background_color', $config);
        $this->assertStringContainsString('--admin-sidebar-menu-hover-background', $service);
        $this->assertStringContainsString('--admin-sidebar-menu-hover-title-color', $service);
        $this->assertStringContainsString('--admin-sidebar-menu-hover-icon-color', $service);
        $this->assertStringContainsString('--admin-sidebar-menu-active-background', $service);
        $this->assertStringContainsString('config.design.sidebar_menu.item.hover_background_mode', $designer);
        $this->assertStringContainsString('config.design.sidebar_menu.item.hover_title_color', $designer);
        $this->assertStringContainsString('config.design.sidebar_menu.item.hover_icon_color', $designer);
    }

    public function test_submenu_visual_state_contract_is_declared_and_resolved(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));

        foreach ([
            'background_mode',
            'background_color',
            'hover_background_mode',
            'hover_background_color',
            'hover_title_color',
            'active_background_mode',
            'active_background_color',
            'active_title_color',
        ] as $token) {
            $this->assertStringContainsString("'{$token}'", $config);
            $this->assertStringContainsString("submenu.{$token}", $component);
        }

        $this->assertStringContainsString('--admin-sidebar-submenu-background', $service);
        $this->assertStringContainsString('--admin-sidebar-submenu-hover-background', $service);
        $this->assertStringContainsString('--admin-sidebar-submenu-hover-title-color', $service);
        $this->assertStringContainsString('--admin-sidebar-submenu-active-background', $service);
        $this->assertStringContainsString('--admin-sidebar-submenu-active-title-color', $service);
    }

    public function test_runtime_sidebar_uses_menu_and_submenu_visual_state_variables(): void
    {
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));

        $this->assertStringContainsString('var(--admin-sidebar-menu-icon-background, transparent)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-hover-background)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-hover-title-color)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-hover-icon-color)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-hover-background)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-menu-active-background)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-background)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-hover-background)', $group);
        $this->assertStringContainsString('var(--admin-sidebar-submenu-active-background)', $group);
    }
}
