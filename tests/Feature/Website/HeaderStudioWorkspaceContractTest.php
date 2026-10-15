<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class HeaderStudioWorkspaceContractTest extends TestCase
{
    public function test_header_workspace_is_preview_first_and_advanced_tools_are_collapsible(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('Thiết kế Header Website', $view);
        $this->assertStringContainsString('id="header-theme-library"', $view);
        $this->assertStringContainsString('id="header-layout-tools"', $view);
        $this->assertStringContainsString('x-show="showLayoutTools"', $view);
        $this->assertStringContainsString('x-show="showThemeLibrary"', $view);
        $this->assertSame(1, substr_count($view, "partials.builder-preview"));
        $this->assertLessThan(strpos($view, 'id="header-layout-tools"'), strpos($view, "partials.builder-preview"));
        $this->assertStringContainsString('wire:click="saveBuilder"', $view);
        $this->assertStringContainsString('wire:click="saveTopbarSettings"', $view);
    }
    public function test_pharma_header_supports_independent_device_topbar_visibility_and_icon_menu(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/builder-preview.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString("'show_desktop' => (bool) (\$values['show_desktop'] ?? true)", $hub);
        $this->assertStringContainsString("'show_mobile' => (bool) (\$values['show_mobile'] ?? false)", $hub);
        $this->assertStringContainsString('wire:model="topbarSettings.show_mobile"', $editor);
        $this->assertStringContainsString('wire:model="topbarSettings.show_desktop"', $editor);
        $this->assertStringContainsString('id="header-account-settings"', $editor);
        $this->assertStringContainsString('id="header-topbar-settings"', $editor);
        $this->assertStringContainsString('id="header-appearance-settings"', $editor);
        $this->assertStringContainsString("topbarSettings['show_mobile']", $preview);
        $this->assertStringContainsString("topbarConfig['show_mobile']", $frontend);
        $this->assertStringContainsString("topbarConfig['show_desktop']", $frontend);
        $this->assertStringContainsString('aria-label="Mở menu điều hướng"', $frontend);
        $this->assertStringContainsString('aria-controls="pharma-mobile-menu"', $frontend);
    }

}
