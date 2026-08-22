<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\FooterComponentRegistry;
use Modules\Website\Services\FooterLayoutService;
use Tests\TestCase;

class WebsiteFooterSchemaConfigurationTest extends TestCase
{
    public function test_registry_resolves_only_known_components_in_allowed_slots(): void
    {
        $registry = app(FooterComponentRegistry::class);

        $this->assertSame('Website::components.footer.brand', $registry->resolve('brand', 'desktop.main.brand')['view']);
        $this->assertSame('Website::components.footer.social-links', $registry->resolve('social-links', 'mobile.main')['view']);

        $this->expectException(\InvalidArgumentException::class);
        $registry->resolve('back-to-top', 'desktop.main.brand');
    }

    public function test_layout_resolver_skips_unknown_disabled_and_misplaced_components(): void
    {
        $layout = app(FooterLayoutService::class)->resolvedLayout([
            'desktop' => [
                'main' => [
                    'brand' => [
                        ['type' => 'brand'],
                        ['type' => 'unknown'],
                        ['type' => 'contact', 'enabled' => false],
                        ['type' => 'back-to-top'],
                    ],
                ],
            ],
            'overlay' => [['type' => 'back-to-top']],
        ]);

        $this->assertCount(1, $layout['desktop.main.brand']);
        $this->assertSame('brand', $layout['desktop.main.brand'][0]['type']);
        $this->assertSame('back-to-top', $layout['overlay'][0]['type']);
    }

    public function test_footer_schema_never_persists_renderer_paths_in_layout_items(): void
    {
        $config = require base_path('Modules/Website/Config/footer.php');
        $encodedLayout = json_encode($config['layout'] ?? []);

        $this->assertIsString($encodedLayout);
        $this->assertStringNotContainsString('Website::', $encodedLayout);
        $this->assertStringNotContainsString('view', $encodedLayout);
    }

    public function test_footer_partial_uses_slot_renderer_instead_of_direct_component_views(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $slot = file_get_contents(base_path('Modules/Website/resources/views/components/footer/slot.blade.php'));

        $this->assertStringContainsString("Website::components.footer.slot", $footer);
        $this->assertStringNotContainsString("Website::components.footer.brand-contact", $footer);
        $this->assertStringNotContainsString("Website::components.footer.app-social", $footer);
        $this->assertStringNotContainsString("Website::components.footer.bottom-bar", $footer);
        $this->assertStringContainsString("@include(\$component['view']", $slot);
    }
}
