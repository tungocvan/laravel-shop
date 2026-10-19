<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioWorkspaceContractTest extends TestCase
{
    public function test_studio_has_full_width_editor_and_always_visible_sticky_preview(): void
    {
        $page = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/footer/index.blade.php'));
        $studio = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString('max-w-none', $page);
        $this->assertStringContainsString('studioPanel', $studio);
        $this->assertStringContainsString("studioPanel === 'profile'", $studio);
        $this->assertStringContainsString('xl:grid-cols-[minmax(0,60fr)_minmax(340px,40fr)]', $studio);
        $this->assertStringContainsString('xl:sticky xl:top-4', $studio);
        $this->assertStringContainsString("partials.builder-preview", $studio);
        $this->assertStringNotContainsString("partials.theme-manager", $preview);
        $this->assertStringContainsString('updateThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
    }
}
