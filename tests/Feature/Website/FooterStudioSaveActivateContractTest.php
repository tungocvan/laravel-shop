<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioSaveActivateContractTest extends TestCase
{
    public function test_studio_has_single_design_panel_and_sticky_preview_actions(): void
    {
        $studio = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString("studioPanel: 'layout'", $studio);
        $this->assertStringContainsString("studioPanel === 'profile'", $studio);
        $this->assertStringContainsString('xl:sticky xl:top-4', $studio);
        $this->assertStringContainsString('Cập nhật tham số vào theme hiện tại', $preview);
        $this->assertStringContainsString('updateThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('Lưu &amp; Kích hoạt theme hiện tại', $preview);
        $this->assertStringContainsString('@disabled(!$selectedTheme)', $preview);
    }

    public function test_save_and_activate_publishes_current_snapshot_in_one_write(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $start = strpos($source, 'public function saveAndPublishTheme(');
        $end = strpos($source, 'public function publishTheme(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($source, $start, $end - $start);
        $this->assertStringContainsString('$this->themeSnapshot(', $method);
        $this->assertStringContainsString("settingKey('published_profile')", $method);
        $this->assertStringContainsString("settingKey('layout_themes')", $method);
        $this->assertStringContainsString("settingKey('active_theme')", $method);
        $this->assertSame(1, substr_count($method, '$settingsService->updateMany('));
        $this->assertStringNotContainsString('$this->publishTheme(', $method);
        $this->assertStringNotContainsString('$this->updateTheme(', $method);
    }
}
