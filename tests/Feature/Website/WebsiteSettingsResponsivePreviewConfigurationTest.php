<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteSettingsResponsivePreviewConfigurationTest extends TestCase
{
    public function test_layout_workspace_exposes_safe_responsive_preview_from_current_form_state(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/layout-presentation.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/responsive-preview.blade.php'));

        $this->assertStringContainsString("partials.responsive-preview", $layout);
        $this->assertStringContainsString('Responsive Preview', $preview);
        $this->assertStringContainsString("previewDevice: 'desktop'", $preview);
        $this->assertStringContainsString("previewDevice='mobile'", $preview);

        foreach (['shell', 'layoutPresentation', 'design', 'appearance', 'features'] as $property) {
            $this->assertStringContainsString("\$wire.entangle('{$property}')", $preview);
        }

        foreach (['header_enabled', 'homepage_enabled', 'footer_enabled', 'maintenance'] as $contract) {
            $this->assertStringContainsString($contract, $preview);
        }

        foreach (['chat_position', 'back_to_top_position', 'application_name', 'theme_color'] as $contract) {
            $this->assertStringContainsString($contract, $preview);
        }

        $this->assertStringNotContainsString('<iframe', strtolower($preview));
        $this->assertStringNotContainsString('serviceWorker.register', $preview);
        $this->assertStringNotContainsString('@livewire(', $preview);
    }

    public function test_pwa_admin_copy_points_to_dynamic_manifest_and_runtime_sync_endpoints(): void
    {
        $appearance = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/appearance.blade.php'));

        $this->assertStringContainsString('/website-manifest.webmanifest', $appearance);
        $this->assertStringContainsString('/website-pwa-version.json', $appearance);
        $this->assertStringNotContainsString('đọc manifest hệ thống tại <code>/manifest.webmanifest</code>', $appearance);
    }
}
