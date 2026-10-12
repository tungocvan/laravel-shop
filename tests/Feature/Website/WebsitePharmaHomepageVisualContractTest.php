<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaHomepageVisualContractTest extends TestCase
{
    #[Test]
    public function homepage_uses_responsive_professional_hero_without_unverified_images(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString('max-w-[1440px]', $view);
        $this->assertStringContainsString('md:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]', $view);
        $this->assertStringContainsString('lg:text-6xl', $view);
        $this->assertStringContainsString('focus-visible:outline', $view);
        $this->assertStringContainsString('aria-hidden="true"', $view);
        $this->assertStringContainsString("route('product.list')", $view);
        $this->assertStringNotContainsString('unsplash.com', $view);
    }

    #[Test]
    public function homepage_keeps_builder_driven_sections_and_verified_product_images(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString('$pharmaHome->sections()', $view);
        $this->assertStringContainsString('$pharmaHome->categories()', $view);
        $this->assertStringContainsString('$pharmaHome->products($section[\'key\'])', $view);
        $this->assertStringContainsString('@if($product->image)', $view);
        $this->assertStringContainsString('Chưa có ảnh sản phẩm', $view);
    }

    #[Test]
    public function pharma_header_and_default_template_remain_separate(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));

        $this->assertStringContainsString('max-w-[1440px]', $header);
        $this->assertStringContainsString("get('site_name', 'Pharma')", $header);
        $this->assertStringContainsString("isPharma()", $layout);
        $this->assertStringContainsString("Website::partials.header", $layout);
    }
}
