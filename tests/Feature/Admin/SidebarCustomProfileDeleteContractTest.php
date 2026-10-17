<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class SidebarCustomProfileDeleteContractTest extends TestCase
{
    public function test_delete_action_is_guarded_and_only_removes_custom_profile(): void
    {
        $source = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $start = strpos($source, 'public function deleteSidebarVisualPreset(');
        $end = strpos($source, 'public function saveSidebarVisualPreset(', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($source, $start, $end - $start);
        $this->assertStringContainsString("abort_unless(\$this->section === 'sidebar', 404)", $method);
        $this->assertStringContainsString('authorizePermission($this->updatePermission())', $method);
        $this->assertStringContainsString("data_get(\$payload, 'sidebar.visual_presets', [])", $method);
        $this->assertStringContainsString('array_key_exists($key, $custom)', $method);
        $this->assertStringContainsString('unset($custom[$key])', $method);
        $this->assertStringContainsString("$"."manager->save(\$payload)", $method);
        $this->assertStringNotContainsString("data_set(\$payload, 'design.sidebar_menu'", $method);
        $this->assertStringNotContainsString("data_set(\$payload, 'sidebar.presentation'", $method);
    }

    public function test_quick_tab_exposes_confirmed_delete_without_nested_buttons(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));
        $this->assertStringContainsString('deleteSidebarVisualPreset(@js($key))', $view);
        $this->assertStringContainsString('wire:confirm="Xóa profile tùy chỉnh này?', $view);
        $this->assertStringContainsString('aria-label="Xóa profile', $view);
        $this->assertStringContainsString('applySidebarQuickProfile(@js($key))', $view);
    }
}
