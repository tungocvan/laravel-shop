<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\WebsiteAppearanceService;
use Tests\TestCase;

class WebsiteAppearanceConfigurationTest extends TestCase
{
    public function test_appearance_service_has_safe_defaults_and_sanitizes_values(): void
    {
        $service = app(WebsiteAppearanceService::class);
        $defaults = $service->defaults('FlexBiz');
        $resolved = $service->resolve([
            'application_name' => '<script>alert(1)</script>',
            'apple_title' => 'Store App',
            'theme_color' => 'red',
            'background_color' => '#ABCDEF',
            'apple_status_bar_style' => 'invalid',
            'manifest_enabled' => false,
            'service_worker_enabled' => false,
        ], 'FlexBiz');

        $this->assertSame('FlexBiz', $defaults['application_name']);
        $this->assertSame('#0f172a', $resolved['theme_color']);
        $this->assertSame('#abcdef', $resolved['background_color']);
        $this->assertSame('default', $resolved['apple_status_bar_style']);
        $this->assertFalse($resolved['manifest_enabled']);
        $this->assertFalse($resolved['service_worker_enabled']);
    }

    public function test_admin_persists_and_exposes_pwa_browser_appearance(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $layoutPartial = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/layout-presentation.blade.php'));
        $appearancePartial = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/appearance.blade.php'));

        $this->assertStringContainsString('public array $appearance = []', $component);
        $this->assertStringContainsString("get('website.appearance')", $component);
        $this->assertStringContainsString("'website.appearance' => \$this->appearance", $component);
        $this->assertStringContainsString('resetAppearance', $component);
        $this->assertStringContainsString('partials.appearance', $layoutPartial);
        $this->assertStringContainsString('appearance.application_name', $appearancePartial);
        $this->assertStringContainsString('appearance.theme_color', $appearancePartial);
        $this->assertStringContainsString('appearance.manifest_enabled', $appearancePartial);
        $this->assertStringContainsString('appearance.service_worker_enabled', $appearancePartial);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $appearancePartial);
    }

    public function test_frontend_metadata_and_service_worker_use_resolved_appearance(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $head = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));
        $scripts = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));

        $this->assertStringContainsString('WebsiteAppearanceService::class', $provider);
        $this->assertStringContainsString("'websiteAppearance'", $provider);
        $this->assertStringContainsString("websiteAppearance['theme_color']", $head);
        $this->assertStringContainsString("websiteAppearance['application_name']", $head);
        $this->assertStringContainsString("'manifest_enabled'", $head);
        $this->assertStringContainsString("'service_worker_enabled'", $scripts);
        $this->assertStringContainsString("navigator.serviceWorker.register('/service-worker.js')", $scripts);
    }
}
