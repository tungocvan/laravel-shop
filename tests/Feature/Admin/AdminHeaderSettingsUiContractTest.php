<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHeaderSettingsUiContractTest extends TestCase
{
    public function test_header_section_uses_dedicated_professional_settings_view(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-header-config.blade.php'));

        $this->assertStringContainsString("if (\$this->section === 'header')", $component);
        $this->assertStringContainsString("Admin::livewire.settings.admin-header-config", $component);
        foreach (['Brand', 'Core components', 'Header Actions', 'UserMenu', 'Presentation & Responsive', 'Header preview'] as $heading) {
            $this->assertStringContainsString($heading, $view);
        }
    }

    public function test_notifications_are_managed_inside_header_actions_not_core_components(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-header-config.blade.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertStringContainsString('data-admin-system-action-settings="notifications"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.notifications"', $view);
        $this->assertStringContainsString('System action', $view);
        $this->assertStringContainsString("['config.header.search' => 'Tìm kiếm trên Header','config.header.user_menu' => 'UserMenu']", $view);
        $this->assertStringContainsString('wire:model.live="config.header.actions.notification.icon"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.actions.notification.behavior"', $view);
        $this->assertStringContainsString("config.header.actions.notification.icon", $component);
        $this->assertStringContainsString("config.header.actions.notification.behavior", $component);
    }

    public function test_current_database_menu_items_are_imported_into_user_menu_editor_when_config_is_empty(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $menuService = file_get_contents(base_path('Modules/Admin/Services/HeaderMenuService.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-header-config.blade.php'));

        $this->assertStringContainsString('HeaderMenuService $headerMenuService', $component);
        $this->assertStringContainsString('exportAdminConfigItems()', $component);
        $this->assertStringContainsString('public bool $importedHeaderMenuItems = false', $component);
        $this->assertStringContainsString('exportAdminConfigItems(): array', $menuService);
        $this->assertStringContainsString("getMenuTreeByLocation('admin')", $menuService);
        $this->assertStringContainsString('Menu items hiện tại', $view);
        $this->assertStringContainsString('$importedHeaderMenuItems', $view);
        $this->assertStringContainsString('Lưu Header', $view);
    }

    public function test_header_editor_supports_safe_dynamic_actions_and_user_menu_items(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-header-config.blade.php'));

        foreach (['addHeaderAction', 'removeHeaderAction', 'addUserMenuItem', 'removeUserMenuItem'] as $method) {
            $this->assertStringContainsString("function {$method}", $component);
            $this->assertStringContainsString($method, $view);
        }

        $this->assertStringContainsString('data-admin-header-actions-editor', $view);
        $this->assertStringContainsString('data-admin-user-menu-editor', $view);
        $this->assertStringContainsString('Thêm action', $view);
        $this->assertStringContainsString('Thêm menu item', $view);
        $this->assertStringContainsString("config.header.actions.items.*.order", $component);
        $this->assertStringContainsString("config.header.user_menu_config.items.*.permission", $component);
        $this->assertStringContainsString('Logout luôn do hệ thống quản lý', $view);
    }

    public function test_brand_runtime_uses_content_width_instead_of_fixed_title_cap(): void
    {
        $brand = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/brand.blade.php'));

        $this->assertStringContainsString('data-admin-header-brand-title', $brand);
        $this->assertStringContainsString('w-auto whitespace-nowrap', $brand);
        $this->assertStringNotContainsString('max-w-44', $brand);
    }

    public function test_header_save_and_reset_reload_shell_after_persistence(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $this->assertStringContainsString("in_array(\$this->section, ['general', 'header'], true)", $component);
        $this->assertStringContainsString('Thiết lập Header đã được lưu và áp dụng.', $component);
        $this->assertStringContainsString('Header đã được khôi phục mặc định và áp dụng.', $component);
        $this->assertStringContainsString("\$this->redirect(url()->previous(), navigate: false)", $component);
    }

    public function test_header_settings_use_live_preview_bindings_for_visual_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-header-config.blade.php'));
        $this->assertStringContainsString('wire:model.live="config.header.brand.enabled"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.height"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.presentation.mode"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.presentation.background"', $view);
        $this->assertStringContainsString('wire:model.live="config.header.responsive.mobile_brand"', $view);
        $this->assertStringContainsString('Preview cập nhật trước khi lưu', $view);
    }
}
