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
        $this->assertStringContainsString("'columns' => ['source' => \$this->columnsSource", $source);
        $this->assertStringContainsString("'social_links' => ['source' => \$this->socialLinksSource", $source);
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
    #[Test]
    public function public_footer_uses_profile_resolver_with_safe_global_fallback(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $resolver = file_get_contents(base_path('Modules/Website/Services/FooterProfileResolver.php'));
        $manager = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/theme-manager.blade.php'));

        $this->assertStringContainsString('FooterProfileResolver::class', $provider);
        $this->assertStringContainsString("'footerColumns'=>\$profileData['columns']", $provider);
        $this->assertStringContainsString("'socialLinks'=>\$profileData['social_links']", $provider);
        $this->assertStringContainsString("'footerSettings'=>\$profileData['settings']", $provider);
        $this->assertStringContainsString("if (\$active === '')", $resolver);
        $this->assertStringContainsString("'source'] ?? 'global'", $resolver);
        $this->assertStringContainsString('wire:model="columnsSource"', $manager);
        $this->assertStringContainsString('wire:model="socialLinksSource"', $manager);
    }

}
