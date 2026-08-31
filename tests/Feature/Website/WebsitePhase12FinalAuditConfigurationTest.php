<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsitePhase12FinalAuditConfigurationTest extends TestCase
{
    public function test_phase_12_storefront_shell_keeps_final_runtime_contracts(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $head = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));
        $scripts = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $worker = file_get_contents(base_path('public/service-worker.js'));

        $this->assertStringContainsString('website-main-shell', $layout);
        $this->assertStringContainsString("'header_enabled'", $layout);
        $this->assertStringContainsString("'footer_enabled'", $layout);
        $this->assertStringContainsString("'maintenance.enabled'", $layout);
        $this->assertStringNotContainsString('@livewireStyles', $layout);
        $this->assertStringNotContainsString('@livewireScripts', $layout);

        $this->assertStringContainsString("route('website.manifest')", $head);
        $this->assertStringNotContainsString('href="/manifest.webmanifest"', $head);
        $this->assertStringContainsString('/website-pwa-version.json', $scripts);
        $this->assertStringContainsString("Route::get('/website-manifest.webmanifest'", $routes);
        $this->assertStringContainsString("Route::get('/website-pwa-version.json'", $routes);
        $this->assertStringContainsString('website-storefront-shell-v4', $worker);
        $this->assertStringContainsString('REFRESH_PWA_ASSETS', $worker);
        $this->assertStringContainsString('SKIP_WAITING', $worker);
        $this->assertStringNotContainsString("'/manifest.webmanifest'", $worker);
    }

    public function test_phase_12_admin_theme_and_preview_contracts_are_safe(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $themeService = file_get_contents(base_path('Modules/Website/Services/WebsiteDesignThemeService.php'));
        $settingsView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $layoutPresentation = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/layout-presentation.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/responsive-preview.blade.php'));

        foreach (['website.design', 'website.shell', 'website.layout', 'website.appearance', 'website.features'] as $setting) {
            $this->assertStringContainsString("'{$setting}'", $component);
        }

        $this->assertStringContainsString('public const VERSION = 2', $themeService);
        $this->assertStringContainsString('public const LEGACY_VERSION = 1', $themeService);
        foreach (["'design'", "'layout'", "'appearance'", "'features'"] as $payload) {
            $this->assertStringContainsString($payload, $themeService);
        }
        foreach (['site_logo', 'site_favicon', 'seo.', 'analytics_code', 'header_script', 'maintenance'] as $unsafe) {
            $this->assertStringNotContainsString("'{$unsafe}' =>", $themeService);
        }

        $this->assertStringContainsString("Website::livewire.admin.settings.partials.layout-presentation", $settingsView);
        $this->assertStringContainsString("Website::livewire.admin.settings.partials.responsive-preview", $layoutPresentation);
        $this->assertStringContainsString('Responsive Preview', $preview);
        $this->assertStringContainsString("previewDevice: 'desktop'", $preview);
        $this->assertStringContainsString("previewDevice==='mobile'", $preview);
        $this->assertStringNotContainsString('<iframe', strtolower($preview));
    }
}
