<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminThemeEditorUxContractTest extends TestCase
{
    public function test_theme_editor_uses_focused_section_navigation_and_deep_link(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        foreach (['Tổng quan', 'Màu sắc', 'Typography', 'Sidebar', 'Menu', 'Header', 'Content & Footer'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString("hash === 'sidebar-menu' ? 'menu'", $view);
        $this->assertStringContainsString('section === \'{{ $sectionKey }}\'', $view);
        $this->assertStringContainsString("@include('Admin::livewire.settings.partials.sidebar-menu-designer')", $view);
        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));
        $this->assertStringContainsString('id="sidebar-menu"', $designer);
        $this->assertStringContainsString("openSection('menu', 'sidebar-menu')", $view);
    }

    public function test_theme_editor_uses_progressive_disclosure_for_advanced_menu_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));
        $this->assertStringContainsString('advancedMenu: false', $view);
        $this->assertStringContainsString('Thiết lập nâng cao', $designer);
        $this->assertStringContainsString('x-show="advancedMenu"', $designer);
        $this->assertStringContainsString('config.design.sidebar_menu.item.padding_x', $designer);
        $this->assertStringContainsString('config.design.sidebar_menu.active.menu_border_width', $designer);
        $this->assertStringContainsString('config.design.sidebar_menu.active.submenu_border_width', $designer);
    }

    public function test_theme_editor_keeps_preview_and_save_actions_sticky(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));
        $this->assertStringContainsString('xl:sticky xl:top-20', $designer);
        $this->assertStringContainsString('Live Menu Preview', $designer);
        $this->assertStringContainsString('sticky bottom-4', $view);
        $this->assertStringContainsString('Lưu & áp dụng Theme', $view);
    }

    public function test_nested_design_changes_refresh_preview_and_are_normalized_before_save(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));
        $presentation = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/presentation-styles.blade.php'));

        $this->assertStringContainsString('public function updated(string $property, mixed $value): void', $component);
        $this->assertStringContainsString("str_starts_with(\$property, 'config.design.')", $component);
        $this->assertStringContainsString('$this->dispatchDesignPreview();', $component);
        $this->assertStringContainsString('$this->normalizeDesign($designService);', $component);
        $this->assertStringContainsString("\$this->config['design'] = \$designService->sanitize(", $component);
        $this->assertStringContainsString("Livewire.on('admin-design-preview'", $presentation);
        $this->assertStringContainsString('document.documentElement.style.setProperty(name, value)', $presentation);
    }
}
