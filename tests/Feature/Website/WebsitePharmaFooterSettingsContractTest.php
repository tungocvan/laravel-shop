<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaFooterSettingsContractTest extends TestCase
{
    #[Test]
    public function shared_footer_builder_scopes_layout_and_saved_profiles_per_template(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $this->assertStringContainsString("get('website.public_template', 'default')", $hub);
        $this->assertStringContainsString('updatedEditingTemplate', $hub);
        $this->assertStringContainsString("'website.pharma_footer.'.\$suffix", $hub);
        $this->assertStringContainsString("'footer.'.\$suffix", $hub);
        $this->assertStringContainsString("settingKey('layout')", $hub);
        $this->assertStringContainsString("settingKey('layout_themes')", $hub);
        $this->assertStringContainsString('saveTheme', $hub);
        $this->assertStringContainsString('applyTheme', $hub);

        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('Template đang quản trị', $editor);
        $this->assertStringContainsString('wire:model.live="editingTemplate"', $editor);
        $this->assertStringNotContainsString('Thiết lập Footer Pharma', $editor);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft', file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php')));

        $composer = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $this->assertStringContainsString("'Website::partials.pharma.footer'", $composer);
        $this->assertStringContainsString("\$footerPrefix.'layout'", $composer);

        foreach (['footer.blade.php', 'pharma/footer.blade.php'] as $path) {
            $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/'.$path));
            $this->assertStringContainsString("Website::partials.footer-shared", $frontend);
        }
    }
}
