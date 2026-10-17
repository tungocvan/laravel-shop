<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class SidebarActiveProfileAndDesignerContractTest extends TestCase
{
    public function test_active_profile_is_persisted_only_on_sidebar_save(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));

        $this->assertStringContainsString("'active_profile_key' => null", $manager);
        $this->assertStringContainsString("'active_profile_key' => is_string(", $manager);
        $this->assertStringContainsString("'sidebar.active_profile_key', null", $component);
        $this->assertStringContainsString("\$validated['sidebar']['active_profile_key'] = \$this->selectedSidebarPresetKey", $component);
        $this->assertStringContainsString('Profile đang sử dụng:', $view);
        $this->assertStringContainsString('Đang xem trước:', $view);
    }

    public function test_designer_is_collapsed_by_default_and_advanced_settings_are_visible_when_open(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));
        $designer = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/partials/sidebar-menu-designer.blade.php'));

        $this->assertStringContainsString('designerOpen: false', $view);
        $this->assertStringContainsString('x-show="designerOpen"', $view);
        $this->assertStringContainsString('Hiển thị Sidebar Menu Designer', $view);
        $this->assertStringNotContainsString('advancedMenu', $designer);
        $this->assertStringContainsString('Layout &amp; Interaction', $designer);
    }
}
