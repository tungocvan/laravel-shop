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
}
