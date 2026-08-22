<?php

namespace Tests\Feature\Website;

use InvalidArgumentException;
use Modules\Website\Services\HomepageSectionRegistry;
use Tests\TestCase;

class WebsiteHomepageSectionRegistryConfigurationTest extends TestCase
{
    public function test_homepage_registry_is_config_driven_and_resolves_canonical_and_copy_sections(): void
    {
        $registry = app(HomepageSectionRegistry::class);
        $sections = $registry->all();

        $this->assertCount(10, $sections);
        $this->assertSame('website.home.hero-banner', $registry->resolve('hero')['renderer']);
        $this->assertSame('category_grid', $registry->resolve('categories')['type']);
        $this->assertSame('website.home.featured-products', $registry->resolve('featured_copy_3', 'product_grid')['renderer']);
        $this->assertSame('website.home.new-arrivals', $registry->resolve('new_arrivals_copy_2', 'product_grid')['renderer']);
        $this->assertSame('website.home.best-sellers', $registry->resolve('best_sellers_copy_8', 'product_grid')['renderer']);
        $this->assertSame(['lazy' => true], $registry->resolve('blog_highlight')['params']);
    }

    public function test_unknown_or_mismatched_homepage_sections_are_rejected(): void
    {
        $registry = app(HomepageSectionRegistry::class);

        try {
            $registry->resolve('unknown-section');
            $this->fail('Unknown Homepage section should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $registry->resolve('featured', 'post_grid');
    }

    public function test_homepage_frontend_uses_registry_renderer_instead_of_switch_statement(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Home/HomeList.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/home-list.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString('HomepageSectionRegistry', $component);
        $this->assertStringContainsString('$registry->resolve(', $component);
        $this->assertStringContainsString('$registry->paramsFor(', $component);
        $this->assertStringContainsString("@livewire(\$render['renderer']", $view);
        $this->assertStringNotContainsString('@switch(', $view);
        $this->assertStringNotContainsString('hidden md:block', $view);
        $this->assertStringContainsString("mergeConfigFrom(__DIR__.'/../Config/homepage.php', 'website.homepage')", $provider);
    }

    public function test_homepage_admin_layout_keys_are_initialized_from_registry(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));

        $this->assertStringContainsString('HomepageSectionRegistry $registry', $component);
        $this->assertStringContainsString("['show_'.\$key => 'all']", $component);
        $this->assertStringContainsString("'homepageSections' => \$registry->all()", $component);
        $this->assertStringNotContainsString("'show_hero' => 'all'", $component);
        $this->assertStringNotContainsString("'show_categories' => 'all'", $component);
    }
}
