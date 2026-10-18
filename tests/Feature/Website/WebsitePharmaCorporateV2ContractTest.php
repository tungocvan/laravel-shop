<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaCorporateV2ContractTest extends TestCase
{
    #[Test]
    public function published_pharma_has_independent_header_and_footer(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $this->assertStringContainsString("website.public_template", $layout);
        $this->assertStringContainsString("Website::partials.pharma.header", $layout);
        $this->assertStringContainsString("Website::partials.pharma.footer", $layout);
        $this->assertStringContainsString("Website::partials.header", $layout);
        $this->assertStringContainsString("Website::partials.footer", $layout);
        $this->assertStringContainsString("get('website.public_template', 'default') === 'pharma'", $layout);
        $this->assertStringNotContainsString("request()->routeIs('product.*')", $layout);
        $this->assertStringNotContainsString("request()->routeIs('home') || request()->routeIs('product.*')", $layout);
    }

    #[Test]
    public function pharma_navigation_does_not_include_retail_checkout_controls(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/footer.blade.php'));
        $this->assertStringContainsString("route('product.list')", $header);
        $this->assertStringContainsString('aria-label="Điều hướng Pharma"', $header);
        $this->assertStringContainsString('aria-label="Điều hướng Pharma trên di động"', $header);
        $this->assertStringContainsString('Website::partials.footer-shared', $footer);
        $builder = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $this->assertStringContainsString('Thông tin trên website mang tính tham khảo', $builder);
        $this->assertStringNotContainsString("route('cart", $header);
        $this->assertStringNotContainsString("route('checkout", $header);
    }

    #[Test]
    public function pharma_home_uses_saved_content_and_accessible_cards(): void
    {
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));
        $this->assertStringContainsString('website.pharma_hero', $home);
        $this->assertStringContainsString('website.pharma_homepage.layout', $home);
        $this->assertStringContainsString('focus-visible:outline', $home);
        $loader = file_get_contents(base_path('Modules/Website/Livewire/Home/PharmaProductSection.php'));
        $cards = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-product-section.blade.php'));
        $this->assertStringContainsString('PharmaProductSection::class', $home);
        $this->assertStringContainsString("whereHas('pharmaLink')", $loader);
        $cardPartial = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma-product-card.blade.php'));
        $this->assertStringContainsString('Website::partials.pharma-product-card', $cards);
        $this->assertStringContainsString('Chưa có ảnh sản phẩm', $cardPartial);
        $this->assertStringContainsString('focus-visible:outline', $cardPartial);
    }
}
