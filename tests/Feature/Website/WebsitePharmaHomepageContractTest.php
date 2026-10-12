<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaHomepageContractTest extends TestCase
{
    #[Test]
    public function pharma_home_uses_existing_homepage_builder_order_and_visibility(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaHomepageService.php'));
        $this->assertStringContainsString('$this->homepage->visibility()', $service);
        $this->assertStringContainsString('$this->homepage->order()', $service);
        $this->assertStringContainsString("'hidden', 'none'", $service);
        $this->assertStringContainsString("'desktop' => 'hidden md:block'", $service);
    }

    #[Test]
    public function pharma_home_uses_only_active_linked_products_and_medicine_categories(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaHomepageService.php'));
        $this->assertStringContainsString("where('type', 'medicine')", $service);
        $this->assertStringContainsString("where('is_active', true)", $service);
        $this->assertStringContainsString("whereHas('pharmaLink')", $service);
        $this->assertStringContainsString("referenceIds('featured', 'product', 'home_featured_ids')", $service);
    }

    #[Test]
    public function pharma_home_is_informational_and_default_home_remains_unchanged(): void
    {
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/index.blade.php'));
        $pharma = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));
        $this->assertStringContainsString("@livewire('website.home.home-list')", $home);
        $this->assertStringContainsString('PharmaHomepageService', $pharma);
        $this->assertStringContainsString("route('product.list')", $pharma);
        $this->assertStringNotContainsString('addToCart', $pharma);
        $this->assertStringNotContainsString('sale_price', $pharma);
    }
}
