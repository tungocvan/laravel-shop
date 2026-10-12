<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaCatalogContractTest extends TestCase
{
    #[Test]
    public function pharma_catalog_only_uses_active_linked_products(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Products/PharmaCatalog.php'));
        $this->assertStringContainsString("where('is_active', true)", $source);
        $this->assertStringContainsString("whereHas('pharmaLink')", $source);
        $this->assertStringContainsString("where('type', 'medicine')", $source);
    }

    #[Test]
    public function pharma_product_views_do_not_offer_checkout_or_discount_badges(): void
    {
        foreach (['livewire/products/pharma-catalog.blade.php', 'products/pharma-show.blade.php'] as $file) {
            $view = file_get_contents(base_path('Modules/Website/resources/views/'.$file));
            $this->assertStringNotContainsString('addToCart', $view);
            $this->assertStringNotContainsString('sale_price', $view);
            $this->assertStringNotContainsString('affiliate', $view);
        }
    }

    #[Test]
    public function public_shop_and_detail_select_pharma_views(): void
    {
        $shop = file_get_contents(base_path('Modules/Website/resources/views/pages/shop.blade.php'));
        $detail = file_get_contents(base_path('Modules/Website/resources/views/products/show.blade.php'));
        $this->assertStringContainsString('website.products.pharma-catalog', $shop);
        $this->assertStringContainsString('Website::products.pharma-show', $detail);
    }
}
