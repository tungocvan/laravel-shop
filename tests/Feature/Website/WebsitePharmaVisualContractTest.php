<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaVisualContractTest extends TestCase
{
    #[Test]
    public function header_does_not_overlay_page_hero(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $this->assertStringContainsString('relative z-30', $view);
        $this->assertStringNotContainsString('sticky top-0', $view);
    }

    #[Test]
    public function catalog_keeps_filters_accessible_and_categories_scrollable(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/products/pharma-catalog.blade.php'));
        $this->assertStringContainsString('max-h-72', $view);
        $this->assertStringContainsString('overflow-y-auto', $view);
        $this->assertStringContainsString('order-1', $view);
        $this->assertStringContainsString('max-w-[1440px]', $view);
    }

    #[Test]
    public function pharma_footer_has_four_column_layout_and_no_fake_contact_data(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/footer.blade.php'));
        $this->assertStringContainsString('lg:grid-cols-4', $view);
        $this->assertStringContainsString("route('product.list')", $view);
        $this->assertStringNotContainsString('info@flexbizpharma', $view);
    }
}
