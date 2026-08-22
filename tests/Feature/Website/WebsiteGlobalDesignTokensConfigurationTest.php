<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteGlobalDesignTokensConfigurationTest extends TestCase
{
    public function test_global_design_tokens_are_resolved_from_settings_with_config_fallback(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignService.php'));

        $this->assertStringContainsString("get('website.design')", $provider);
        $this->assertStringContainsString('WebsiteDesignService::class', $provider);
        $this->assertStringContainsString("'website.design' => \$this->design", $component);
        $this->assertStringContainsString("config('website.design', [])", $service);
        $this->assertStringContainsString("preg_match('/^#[0-9a-fA-F]{6}$/'", $service);
        $this->assertStringContainsString("preg_match('/[;{}<>]/'", $service);
    }

    public function test_website_settings_exposes_global_design_admin_with_standard_inputs(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));

        $this->assertStringContainsString("'design'=>'Thiết kế toàn site'", $view);
        $this->assertStringContainsString('Global Design Tokens', $view);
        $this->assertStringContainsString('design.typography.font_family_body', $view);
        $this->assertStringContainsString('design.typography.base_font_size', $view);
        $this->assertStringContainsString('design.colors.{{ $key }}', $view);
        $this->assertStringContainsString('design.layout.container_width.{{ $key }}', $view);
        $this->assertStringContainsString('design.layout.radius.{{ $key }}', $view);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $view);
        $this->assertStringContainsString('sticky bottom-4', $view);
        $this->assertStringContainsString('resetDesign', $component);
    }
}
