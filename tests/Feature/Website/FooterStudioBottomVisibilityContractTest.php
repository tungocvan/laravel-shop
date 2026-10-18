<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioBottomVisibilityContractTest extends TestCase
{
    public function test_bottom_bar_visibility_is_saved_in_profile_and_used_on_frontend(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $this->assertStringContainsString('public bool $bottomBarEnabled = true;', $hub);
        $this->assertStringContainsString("'bottom_bar_enabled' => $this->bottomBarEnabled", $hub);
        $this->assertStringContainsString('public function loadBottomBarSample(', $hub);

        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $this->assertStringContainsString("'footerBottomBarEnabled'", $provider);

        $public = file_get_contents(base_path('Modules/Website/resources/views/partials/footer-shared.blade.php'));
        $this->assertStringContainsString('@if($footerBottomBarEnabled ?? true)', $public);

        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $this->assertGreaterThanOrEqual(2, substr_count($preview, '@if($bottomBarEnabled)'));
    }

    public function test_studio_has_sample_action_and_collapsible_sections_preserve_self(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('wire:click="loadBottomBarSample"', $view);
        $this->assertStringContainsString('wire:model.live="bottomBarEnabled"', $view);
        $this->assertStringContainsString('<details wire:ignore.self wire:key="footer-column-editor-', $view);
        $this->assertStringContainsString('<details wire:ignore.self x-show="studioPanel', $view);
    }
}
