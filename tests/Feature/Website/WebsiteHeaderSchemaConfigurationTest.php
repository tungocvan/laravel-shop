<?php

namespace Tests\Feature\Website;

use InvalidArgumentException;
use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderLayoutService;
use Tests\TestCase;

class WebsiteHeaderSchemaConfigurationTest extends TestCase
{
    public function test_registry_resolves_only_known_components_in_allowed_slots(): void
    {
        $registry = app(HeaderComponentRegistry::class);

        $brand = $registry->resolve('brand', 'desktop.main.left');
        $this->assertSame('Website::components.header.brand', $brand['view']);

        // Phase 9D allows movable desktop components across left/center/right slots.
        $movedBrand = $registry->resolve('brand', 'desktop.main.right');
        $this->assertSame('Website::components.header.brand', $movedBrand['view']);

        $this->expectException(InvalidArgumentException::class);
        $registry->resolve('mobile-menu', 'desktop.main.right');
    }

    public function test_layout_resolver_skips_unknown_disabled_or_misplaced_components(): void
    {
        $service = app(HeaderLayoutService::class);

        $layout = $service->resolvedLayout([
            'desktop' => [
                'topbar' => [['type' => 'topbar']],
                'main' => [
                    'left' => [
                        ['type' => 'brand'],
                        ['type' => 'unknown-component'],
                        ['type' => 'search', 'enabled' => false],
                    ],
                    'center' => [['type' => 'mobile-menu']],
                    'right' => [['type' => 'actions']],
                ],
            ],
            'mobile' => [
                'search' => [['type' => 'search', 'config' => ['mode' => 'mobile']]],
                'drawer' => [['type' => 'mobile-menu']],
            ],
        ]);

        $this->assertCount(1, $layout['desktop.main.left']);
        $this->assertSame('brand', $layout['desktop.main.left'][0]['type']);
        $this->assertSame([], $layout['desktop.main.center']);
        $this->assertSame('mobile', $layout['mobile.search'][0]['config']['mode']);
    }

    public function test_header_schema_never_persists_renderer_paths_in_layout_items(): void
    {
        $layout = config('website.header.layout');
        $encoded = json_encode($layout, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('Website::', $encoded);
        $this->assertStringNotContainsString('.blade.php', $encoded);
    }

    public function test_header_partial_uses_slot_renderer_instead_of_direct_component_views(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/header.blade.php'));

        $this->assertStringContainsString('components.header.slot', $header);
        $this->assertStringNotContainsString("components.header.brand'", $header);
        $this->assertStringNotContainsString("components.header.actions'", $header);
    }
}
