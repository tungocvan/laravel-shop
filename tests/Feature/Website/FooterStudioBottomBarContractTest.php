<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioBottomBarContractTest extends TestCase
{
    public function test_bottom_bar_has_visible_editor_and_existing_builder_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('Thanh cuối Footer · Bottom Bar', $view);
        $this->assertStringContainsString("'desktop.bottom.left' =>", $view);
        $this->assertStringContainsString("'desktop.bottom.right' =>", $view);
        $this->assertStringContainsString('wire:click="toggleComponent(', $view);
        $this->assertStringContainsString('wire:click="moveUp(', $view);
        $this->assertStringContainsString('wire:click="moveDown(', $view);
        $this->assertStringContainsString('profileContent.legal.overrides.copyright', $view);
    }

    public function test_bottom_bar_changes_use_versioned_snapshot_and_public_layout(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $public = file_get_contents(base_path('Modules/Website/resources/views/partials/footer-shared.blade.php'));
        $this->assertStringContainsString("'layout' => \$this->safeLayout(\$this->builderSlots, \$registry)", $component);
        $this->assertStringContainsString("'content' => \$this->profileContent", $component);
        $this->assertStringContainsString("if (\$key === 'legal.overrides.copyright')", $component);
        $this->assertStringContainsString("data_get(\$profileContent, 'legal.overrides.copyright')", $preview);
        $this->assertStringContainsString("'desktop.bottom.left'", $public);
        $this->assertStringContainsString("'desktop.bottom.right'", $public);
    }
}
