<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterBrandThemeConfigurationTest extends TestCase
{
    public function test_footer_brand_logo_has_site_logo_fallback_and_safe_upload_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterInfo.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString('WithFileUploads', $component);
        $this->assertStringContainsString("'footer.brand_logo'", $component);
        $this->assertStringContainsString("'footer/brand'", $component);
        $this->assertStringContainsString("'image'", $component);
        $this->assertStringContainsString("'mimes:jpg,jpeg,png,webp'", $component);
        $this->assertStringContainsString("'max:3072'", $component);
        $this->assertStringContainsString("str_starts_with(\$path, 'footer/brand/')", $component);
        $this->assertStringContainsString('removeBrandLogo', $component);

        $this->assertStringContainsString('wire:model="brand_logo_upload"', $view);
        $this->assertStringContainsString('Fallback từ site_logo', $view);
        $this->assertStringContainsString('wire:click="removeBrandLogo"', $view);

        $this->assertStringContainsString("\$footerBrandLogo = \$settings->get('footer.brand_logo')", $provider);
        $this->assertStringContainsString("'logo' => \$footerBrandLogo ?: \$settings->get('site_logo')", $provider);
    }

    public function test_footer_layout_themes_snapshot_only_layout_and_presentation(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $themeView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/theme-manager.blade.php'));

        $this->assertStringContainsString("'footer.layout_themes'", $component);
        $this->assertStringContainsString('THEME_VERSION = 1', $component);
        $this->assertStringContainsString('MAX_THEMES = 20', $component);
        $this->assertStringContainsString('saveTheme(', $component);
        $this->assertStringContainsString('applyTheme(', $component);
        $this->assertStringContainsString('updateTheme(', $component);
        $this->assertStringContainsString('renameTheme(', $component);
        $this->assertStringContainsString('deleteTheme(', $component);
        $this->assertStringContainsString("'layout' => \$this->safeLayout", $component);
        $this->assertStringContainsString("'presentation' => \$presentationService->resolve", $component);
        $this->assertStringNotContainsString("'view' =>", $component);
        $this->assertStringNotContainsString("'footerColumns'", $component);
        $this->assertStringNotContainsString("'socialLinks'", $component);
        $this->assertStringNotContainsString("'footer.brand_logo'", $component);

        $this->assertStringContainsString('Footer Layout Themes', $themeView);
        $this->assertStringContainsString('wire:click="saveTheme"', $themeView);
        $this->assertStringContainsString('wire:click="applyTheme"', $themeView);
        $this->assertStringContainsString('wire:click="updateTheme"', $themeView);
        $this->assertStringContainsString('wire:click="renameTheme"', $themeView);
        $this->assertStringContainsString('wire:click="deleteTheme"', $themeView);
        $this->assertStringContainsString('frontend chỉ thay đổi sau khi bấm', $themeView);
    }

    public function test_theme_apply_is_preview_first_and_publish_stays_in_save_builder(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));

        $applyStart = strpos($component, 'public function applyTheme(');
        $updateStart = strpos($component, 'public function updateTheme(');
        $this->assertNotFalse($applyStart);
        $this->assertNotFalse($updateStart);

        $applyMethod = substr($component, $applyStart, $updateStart - $applyStart);
        $this->assertStringContainsString('loadBuilderLayout', $applyMethod);
        $this->assertStringContainsString('$this->presentation =', $applyMethod);
        $this->assertStringNotContainsString('footer.layout', $applyMethod);
        $this->assertStringNotContainsString('updateMany', $applyMethod);

        $this->assertStringContainsString("'footer.layout' => \$layout", $component);
        $this->assertStringContainsString("'footer.presentation' => \$presentation", $component);
    }
}
