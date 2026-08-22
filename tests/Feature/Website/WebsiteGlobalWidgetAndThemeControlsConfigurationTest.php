<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteGlobalWidgetAndThemeControlsConfigurationTest extends TestCase
{
    public function test_theme_name_validation_and_default_restore_are_exposed(): void
    {
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/design-themes.blade.php'));

        $this->assertStringContainsString("'themeName' => 'required|string|min:1|max:80'", $concern);
        $this->assertStringContainsString('restoreDefaultDesignThemes', $concern);
        $this->assertStringContainsString('restoreDefaultThemes', $service);
        $this->assertStringContainsString('demo-classic-blue', $service);
        $this->assertStringContainsString('demo-commerce-emerald', $service);
        $this->assertStringContainsString('demo-premium-violet', $service);
        $this->assertStringContainsString("@error('themeName')", $view);
        $this->assertStringContainsString('Khôi phục themes mặc định', $view);
    }

    public function test_chat_and_back_to_top_have_global_visibility_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $adminView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $backToTop = file_get_contents(base_path('Modules/Website/resources/views/components/footer/back-to-top.blade.php'));

        $this->assertStringContainsString("'chat_widget' => true", $component);
        $this->assertStringContainsString("'back_to_top' => true", $component);
        $this->assertStringContainsString("'website.features' => \$this->features", $component);
        $this->assertStringContainsString('features.chat_widget', $adminView);
        $this->assertStringContainsString('features.back_to_top', $adminView);
        $this->assertStringContainsString("get('website.features')", $provider);
        $this->assertStringContainsString("data_get(\$websiteFeatures ?? [], 'chat_widget', true)", $footer);
        $this->assertStringContainsString("data_get(\$websiteFeatures ?? [], 'back_to_top', true)", $backToTop);
    }
}
