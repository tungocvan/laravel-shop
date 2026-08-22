<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\FooterPresentationService;
use Tests\TestCase;

class WebsiteFooterPresentationConfigurationTest extends TestCase
{
    public function test_footer_presentation_defaults_match_current_layout(): void
    {
        $resolved = app(FooterPresentationService::class)->resolve();

        $this->assertSame('basic', $resolved['mode']);
        $this->assertSame('standard', $resolved['container']);
        $this->assertSame(1280, $resolved['container_width']);
        $this->assertSame(64, $resolved['padding_top']);
        $this->assertSame(32, $resolved['padding_bottom']);
        $this->assertSame(48, $resolved['column_gap']);
        $this->assertSame(64, $resolved['section_gap']);
        $this->assertTrue($resolved['inherit_colors']);
    }

    public function test_footer_advanced_values_are_clamped_and_colors_are_validated(): void
    {
        $resolved = app(FooterPresentationService::class)->resolve([
            'mode' => 'advanced',
            'inherit_colors' => false,
            'colors' => ['background' => 'javascript:bad', 'accent' => '#ABCDEF'],
            'custom' => [
                'container_width' => 99999,
                'padding_top' => 1,
                'padding_bottom' => 999,
                'column_gap' => 1,
                'section_gap' => 999,
                'logo_max_height' => 999,
                'social_icon_size' => 1,
            ],
        ]);

        $this->assertSame(1920, $resolved['container_width']);
        $this->assertSame(24, $resolved['padding_top']);
        $this->assertSame(96, $resolved['padding_bottom']);
        $this->assertSame(16, $resolved['column_gap']);
        $this->assertSame(120, $resolved['section_gap']);
        $this->assertSame(72, $resolved['custom']['logo_max_height']);
        $this->assertSame(32, $resolved['custom']['social_icon_size']);
        $this->assertSame('#111827', $resolved['colors']['background']);
        $this->assertSame('#abcdef', $resolved['colors']['accent']);
    }

    public function test_footer_partial_consumes_resolved_presentation_tokens(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));

        $this->assertStringContainsString("get('footer.presentation')", $provider);
        $this->assertStringContainsString('FooterPresentationService::class', $provider);
        $this->assertStringContainsString('--footer-background', $footer);
        $this->assertStringContainsString('--footer-padding-top', $footer);
        $this->assertStringContainsString('--footer-column-gap', $footer);
        $this->assertStringContainsString('--footer-logo-max', $footer);
    }
}
