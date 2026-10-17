<?php

namespace Modules\Website\Tests\Feature;

use Modules\Website\Services\PharmaProductCardStyle;
use Tests\TestCase;

class PharmaCatalogCardBuilderTest extends TestCase
{
    public function test_catalog_uses_shared_card_with_independent_setting_and_responsive_grid(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/ProductController.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/shop-pharma.blade.php'));
        $admin = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));

        $this->assertStringContainsString('website.pharma_catalog.card_design', $controller);
        $this->assertStringContainsString('website.pharma_catalog.card_design', $admin);
        $this->assertStringContainsString("Website::partials.pharma-product-card", $view);
        $this->assertStringContainsString('data-product-card-grid', $view);
        $this->assertStringContainsString('grid-template-columns:repeat(var(--pharma-product-cols)', $view);
        $this->assertStringContainsString("'cardShowPrice' => \$authenticated", $view);
        $this->assertStringContainsString('savePharmaCatalogCard', $editor);
        $this->assertStringContainsString('copyHomepageCardToCatalog', $editor);
        $this->assertStringContainsString('pharmaCatalogCard.fallback_image_url', $editor);
        $this->assertStringContainsString('pharmaProductFilters.', $editor);
        $this->assertStringContainsString('aria-label="Bộ lọc danh mục"', $view);
        $this->assertStringContainsString('lg:grid-cols-4', $view);
        $this->assertStringContainsString('aria-label="Phân trang sản phẩm"', $view);
        $this->assertStringContainsString('$products->getUrlRange(', $view);
        $this->assertStringContainsString('$products->previousPageUrl()', $view);
    }

    public function test_catalog_design_normalizes_invalid_input(): void
    {
        $style = app(PharmaProductCardStyle::class)->normalize(['preset' => 'bad', 'desktop_columns' => 999, 'background' => 'invalid']);
        $this->assertSame('classic', $style['preset']);
        $this->assertSame(4, $style['desktop_columns']);
        $this->assertSame('#ffffff', $style['background']);
    }

    public function test_catalog_and_homepage_share_card_but_not_settings(): void
    {
        $homepage = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-product-section.blade.php'));
        $catalog = file_get_contents(base_path('Modules/Website/resources/views/pages/shop-pharma.blade.php'));
        $this->assertStringContainsString("Website::partials.pharma-product-card", $homepage);
        $this->assertStringContainsString("Website::partials.pharma-product-card", $catalog);
        $this->assertStringNotContainsString('website.pharma_homepage.product_sections', $catalog);
    }
}
