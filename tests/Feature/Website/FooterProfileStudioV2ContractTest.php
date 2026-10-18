<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FooterProfileStudioV2ContractTest extends TestCase
{
    #[Test]
    public function footer_profile_has_explicit_activation_and_versioned_snapshot(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $this->assertStringContainsString('private const THEME_VERSION = 2;', $source);
        $this->assertStringContainsString("settingKey('active_theme')", $source);
        $this->assertStringContainsString("'columns' => ['source' => $this->columnsSource", $source);
        $this->assertStringContainsString("'social_links' => ['source' => $this->socialLinksSource", $source);
        $this->assertStringContainsString("'content' => \$this->profileContent", $source);
        $this->assertStringContainsString('public function publishTheme(', $source);
        $this->assertStringContainsString('Không thể xóa profile đang kích hoạt', $source);
    }

    #[Test]
    public function profile_load_does_not_publish_and_ui_exposes_active_status(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $start = strpos($source, 'public function applyTheme(');
        $end = strpos($source, 'public function updateTheme(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $apply = substr($source, $start, $end - $start);
        $this->assertStringNotContainsString('updateMany(', $apply);

        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/theme-manager.blade.php'));
        $this->assertStringContainsString('wire:click="publishTheme"', $view);
        $this->assertStringContainsString('Active profile:', $view);
    }
}
