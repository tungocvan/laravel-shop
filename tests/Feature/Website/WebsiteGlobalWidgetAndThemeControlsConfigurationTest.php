<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteGlobalWidgetAndThemeControlsConfigurationTest extends TestCase
{
    public function test_theme_name_sync_validation_restore_modal_and_import_guards_are_exposed(): void
    {
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/Concerns/ManagesWebsiteDesignThemes.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/design-themes.blade.php'));

        $this->assertStringContainsString("'themeName' => 'required|string|min:1|max:80'", $concern);
        $this->assertStringContainsString('updatedSelectedTheme', $concern);
        $this->assertStringContainsString("\$this->themeName = is_array(\$theme)", $concern);
        $this->assertStringContainsString('restoreDefaultDesignThemes', $concern);
        $this->assertStringContainsString("'themeJson' => 'required|string|min:2'", $concern);
        $this->assertStringContainsString("catch (InvalidArgumentException \$exception)", $concern);
        $this->assertStringContainsString("addError('themeJson'", $concern);
        $this->assertStringContainsString("trim(\$json) === ''", $service);
        $this->assertStringContainsString("['typography', 'colors', 'layout']", $service);
        $this->assertStringContainsString('restoreDefaultThemes', $service);
        foreach (['demo-classic-blue', 'demo-commerce-emerald', 'demo-premium-violet'] as $slug) {
            $this->assertStringContainsString($slug, $service);
        }
        $this->assertStringContainsString("@error('themeName')", $view);
        $this->assertStringContainsString("@error('themeJson')", $view);
        $this->assertStringContainsString('modalOpen', $view);
        $this->assertStringContainsString("confirm('apply'", $view);
        $this->assertStringContainsString("confirm('save'", $view);
        $this->assertStringContainsString("confirm('update'", $view);
        $this->assertStringContainsString('Khôi phục themes mặc định', $view);
    }

    public function test_chat_and_back_to_top_have_three_safe_global_positions(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $adminView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $backToTop = file_get_contents(base_path('Modules/Website/resources/views/components/footer/back-to-top.blade.php'));
        $chatComponent = file_get_contents(base_path('Modules/Website/Livewire/Chat/ChatWidget.php'));
        $chatView = file_get_contents(base_path('Modules/Website/resources/views/livewire/chat/chat-widget.blade.php'));

        foreach (['chat_widget', 'chat_position', 'back_to_top', 'back_to_top_position'] as $key) {
            $this->assertStringContainsString("'{$key}'", $component);
        }
        $this->assertStringContainsString("'website.features' => \$this->features", $component);
        $this->assertStringContainsString("return ['bottom-left', 'bottom-right', 'right-middle'];", $component);
        $this->assertStringContainsString('features.chat_position', $adminView);
        $this->assertStringContainsString('features.back_to_top_position', $adminView);
        $this->assertStringContainsString('Góc trái dưới', $adminView);
        $this->assertStringContainsString('Góc phải dưới', $adminView);
        $this->assertStringContainsString('Bên phải giữa', $adminView);
        $this->assertStringContainsString("['bottom-left','bottom-right','right-middle']", $provider);
        $this->assertStringContainsString("['position' => data_get(\$websiteFeatures", $footer);
        $this->assertStringContainsString("'right-middle'", $backToTop);
        $this->assertStringContainsString("public string \$position = 'bottom-right'", $chatComponent);
        $this->assertStringContainsString("['bottom-left', 'bottom-right', 'right-middle']", $chatComponent);
        $this->assertStringContainsString('$chatMiddleRight', $chatView);
        $this->assertStringContainsString('right-20 top-1/2', $chatView);
    }
}
