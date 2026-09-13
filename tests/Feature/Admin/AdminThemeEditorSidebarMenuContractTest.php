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

    public function test_theme_editor_exposes_icon_color_and_background_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        $this->assertStringContainsString('wire:model.live="config.design.sidebar_menu.item.icon_color"', $view);
        $this->assertStringContainsString('wire:model.live="config.design.sidebar_menu.item.icon_background_mode"', $view);
        $this->assertStringContainsString('wire:model.live="config.design.sidebar_menu.item.icon_background_color"', $view);
        $this->assertStringContainsString('<option value="transparent">Trong suốt</option>', $view);
        $this->assertStringContainsString('<option value="color">Dùng màu nền</option>', $view);
    }

    public function test_runtime_sidebar_uses_icon_background_design_variable(): void
    {
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));

        $this->assertStringContainsString('var(--admin-sidebar-menu-icon-background, transparent)', $item);
        $this->assertStringContainsString('var(--admin-sidebar-menu-icon-background, transparent)', $group);
    }
}
