<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaDesignSystemContractTest extends TestCase
{
    #[Test]
    public function pharma_header_has_brand_identity_and_responsive_menu(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('max-w-[1440px]', $view);
        $this->assertStringContainsString('aria-controls="pharma-mobile-menu"', $view);
        $this->assertStringContainsString("get('site_name', 'Pharma')", $view);
    }

    #[Test]
    public function catalog_supports_category_search_and_mobile_first_cards(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Products/PharmaCatalog.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/products/pharma-catalog.blade.php'));

        $this->assertStringContainsString('public string $categorySearch', $component);
        $this->assertStringContainsString('wire:model.live.debounce.250ms="categorySearch"', $view);
        $this->assertStringContainsString('mb_strtolower(trim($categorySearch))', $view);
        $this->assertStringContainsString('aspect-[5/3] sm:aspect-[5/4]', $view);
        $this->assertStringContainsString('Không tìm thấy danh mục.', $view);
    }

    #[Test]
    public function detail_remains_informational_and_uses_readable_content(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/products/pharma-show.blade.php'));

        $this->assertStringContainsString('Hồ sơ sản phẩm', $view);
        $this->assertStringContainsString('break-words text-base leading-8', $view);
        $this->assertStringNotContainsString('addToCart', $view);
    }
}
