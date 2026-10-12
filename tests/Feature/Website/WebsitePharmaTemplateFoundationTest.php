<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\WebsiteTemplateService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaTemplateFoundationTest extends TestCase
{
    #[Test]
    public function template_service_only_accepts_supported_values(): void
    {
        $this->assertSame('default', WebsiteTemplateService::DEFAULT);
        $this->assertSame('pharma', WebsiteTemplateService::PHARMA);
    }

    #[Test]
    public function homepage_preserves_existing_default_component_and_adds_pharma_branch(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/home/index.blade.php'));

        $this->assertStringContainsString("website.home.home-list", $view);
        $this->assertStringContainsString("Website::pages.home.pharma", $view);
        $this->assertStringContainsString("WebsiteTemplateService::class", $view);
    }

    #[Test]
    public function pharma_homepage_links_to_canonical_product_route(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString("route('product.list')", $view);
        $this->assertStringNotContainsString('Medicine::query()', $view);
    }
}
