<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterVisualWorkspaceContractTest extends TestCase
{
    public function test_visual_workspace_places_preview_first_and_keeps_theme_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        $this->assertStringContainsString('xl:order-1', $view);
        $this->assertStringContainsString('xl:order-2', $view);
        $this->assertStringContainsString('builder-preview', $view);
        $this->assertStringContainsString('theme-manager', $view);
        $this->assertStringContainsString('Bố cục & Nội dung', $view);
    }

    public function test_responsive_preview_shows_tablet_and_draft_column_items(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString("previewDevice = 'tablet'", $preview);
        $this->assertStringContainsString("max-w-[768px]", $preview);
        $this->assertStringContainsString("previewDevice !== 'mobile'", $preview);
        $this->assertStringContainsString("array_slice(\$column['items'] ?? [], 0, 8)", $preview);
        $this->assertStringContainsString('saveAndPublishTheme', $preview);
    }
}
