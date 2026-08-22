<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\HomepageSectionRegistry;
use Tests\TestCase;

class WebsiteHomepageSectionAdminActionConfigurationTest extends TestCase
{
    public function test_homepage_registry_exposes_safe_component_admin_actions(): void
    {
        $registry = app(HomepageSectionRegistry::class);

        $banner = $registry->adminAction('hero');
        $this->assertSame('route', $banner['type']);
        $this->assertSame('admin.banners', $banner['route']);
        $this->assertSame('Quản trị Banner', $banner['label']);

        $flashSale = $registry->adminAction('flash_sale');
        $this->assertSame('admin.flash-sales', $flashSale['route']);

        $categories = $registry->adminAction('categories_copy_1');
        $this->assertSame('tab', $categories['type']);
        $this->assertSame('categories', $categories['tab']);

        $featured = $registry->adminAction('featured');
        $this->assertSame('featured', $featured['tab']);

        $autoQuery = $registry->adminAction('new_arrivals');
        $this->assertSame('auto_query', $autoQuery['tab']);

        $promo = $registry->adminAction('promo_banner');
        $this->assertSame('promo_banner', $promo['tab']);

        $newsletter = $registry->adminAction('newsletter');
        $this->assertSame('newsletter', $newsletter['tab']);

        $trustBadges = $registry->adminAction('trust_badges');
        $this->assertSame('trust_badges', $trustBadges['tab']);
    }

    public function test_admin_routes_are_metadata_not_renderer_or_database_content(): void
    {
        $config = file_get_contents(base_path('Modules/Website/Config/homepage.php'));
        $registry = file_get_contents(base_path('Modules/Website/Services/HomepageSectionRegistry.php'));

        $this->assertStringContainsString("'route' => 'admin.banners'", $config);
        $this->assertStringContainsString("'route' => 'admin.flash-sales'", $config);
        $this->assertStringContainsString("'tab' => 'categories'", $config);
        $this->assertStringContainsString("'tab' => 'featured'", $config);
        $this->assertStringContainsString("'tab' => 'auto_query'", $config);
        $this->assertStringContainsString('Route::has', $registry);
        $this->assertStringContainsString('public function adminAction', $registry);
    }
}
