<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteDesignTokensConfigurationTest extends TestCase
{
    public function test_phase_9b_design_config_exposes_required_global_tokens(): void
    {
        $design = config('website.design');

        $this->assertIsArray($design);
        $this->assertSame('16px', data_get($design, 'typography.base_font_size'));
        $this->assertSame('#2563eb', data_get($design, 'colors.primary'));
        $this->assertSame('#f9fafb', data_get($design, 'colors.background'));
        $this->assertSame('#111827', data_get($design, 'colors.text'));
        $this->assertSame('standard', data_get($design, 'layout.default_container'));
        $this->assertSame('1280px', data_get($design, 'layout.container_width.standard'));
    }

    public function test_frontend_layout_renders_global_design_token_partial_and_semantic_classes(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $runtimeHead = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));
        $tokens = file_get_contents(base_path('Modules/Website/resources/views/partials/design-tokens.blade.php'));
        $css = file_get_contents(base_path('resources/css/tailwind.css'));

        $this->assertStringContainsString("@include('Website::partials.layout.runtime-head')", $layout);
        $this->assertStringContainsString("@include('Website::partials.design-tokens')", $runtimeHead);
        $this->assertStringContainsString('text-website-text', $layout);
        $this->assertStringContainsString('font-website-body', $layout);

        $this->assertStringContainsString('--website-font-body:', $tokens);
        $this->assertStringContainsString('--website-color-primary:', $tokens);
        $this->assertStringContainsString('--website-container-standard:', $tokens);

        $this->assertStringContainsString('--color-website-primary:', $css);
        $this->assertStringContainsString('--font-website-body:', $css);
        $this->assertStringContainsString('--website-font-heading', $css);
    }

    public function test_phase_9b_does_not_introduce_header_builder_persistence(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $designConfig = file_get_contents(base_path('Modules/Website/Config/design.php'));

        $this->assertStringContainsString("mergeConfigFrom(__DIR__.'/../Config/design.php', 'website.design')", $provider);
        $this->assertStringContainsString("\$savedDesign=\$settings->get('website.design')", $provider);
        $this->assertStringContainsString('WebsiteDesignService::class', $provider);
        $this->assertStringContainsString("'websiteDesign'=>\$websiteDesign", $provider);
        $this->assertStringNotContainsString('website_header_layouts', $provider.$designConfig);
    }
}
