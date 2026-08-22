<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\WebsiteLayoutPresentationService;
use Tests\TestCase;

class WebsiteLayoutPresentationConfigurationTest extends TestCase
{
    public function test_layout_presentation_has_safe_defaults_and_sanitized_contract(): void
    {
        $service = app(WebsiteLayoutPresentationService::class);
        $defaults = $service->defaults();
        $resolved = $service->resolve([
            'body' => ['background' => 'javascript:alert(1)'],
            'main' => [
                'container' => 'invalid',
                'background' => 'surface',
                'alignment' => 'center',
                'desktop' => ['padding_top' => 999],
            ],
            'scroll' => ['smooth' => true],
        ]);

        $this->assertSame(32, $defaults['main']['desktop']['padding_top']);
        $this->assertSame('full', $defaults['main']['container']);
        $this->assertSame('background', $resolved['body']['background']);
        $this->assertSame('full', $resolved['main']['container']);
        $this->assertSame(32, $resolved['main']['desktop']['padding_top']);
        $this->assertSame('surface', $resolved['main']['background']);
        $this->assertTrue($resolved['scroll']['smooth']);
    }

    public function test_admin_persists_layout_presentation_and_exposes_standard_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));
        $partial = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/partials/layout-presentation.blade.php'));

        $this->assertStringContainsString('public array $layoutPresentation = []', $component);
        $this->assertStringContainsString("get('website.layout')", $component);
        $this->assertStringContainsString("'website.layout' => \$this->layoutPresentation", $component);
        $this->assertStringContainsString('resetLayoutPresentation', $component);
        $this->assertStringContainsString("Website::livewire.admin.settings.partials.layout-presentation", $view);
        $this->assertStringContainsString("['desktop' => 'Desktop', 'mobile' => 'Mobile']", $partial);
        $this->assertStringContainsString('layoutPresentation.main.{{ $device }}.padding_top', $partial);
        $this->assertStringContainsString('layoutPresentation.main.{{ $device }}.padding_bottom', $partial);
        $this->assertStringContainsString('layoutPresentation.main.{{ $device }}.padding_x', $partial);
        $this->assertStringContainsString('layoutPresentation.main.container', $partial);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $view);
    }

    public function test_frontend_shell_uses_resolved_layout_presentation_instead_of_hardcoded_py_8(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $runtimeHead = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));
        $styles = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/presentation-styles.blade.php'));

        $this->assertStringContainsString("get('website.layout')", $provider);
        $this->assertStringContainsString('WebsiteLayoutPresentationService::class', $provider);
        $this->assertStringContainsString("'websiteLayoutPresentation'", $provider);
        $this->assertStringContainsString('website-main-shell', $layout);
        $this->assertStringNotContainsString('class="py-8 w-full flex-grow"', $layout);
        $this->assertStringContainsString('Website::partials.layout.presentation-styles', $runtimeHead);
        $this->assertStringContainsString('--website-main-padding-top', $styles);
        $this->assertStringContainsString('@media (max-width: 767px)', $styles);
        $this->assertStringContainsString('scroll-behavior:', $styles);
    }
}
