<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaShellContractTest extends TestCase
{
    #[Test]
    public function layout_chooses_pharma_header_and_footer_without_changing_default_shell(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $this->assertStringContainsString("Website::partials.pharma.header", $view);
        $this->assertStringContainsString("Website::partials.pharma.footer", $view);
        $this->assertStringContainsString("Website::partials.header", $view);
        $this->assertStringContainsString("Website::partials.footer", $view);
    }

    #[Test]
    public function pharma_header_has_accessible_search_and_navigation(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $this->assertStringContainsString('name="search"', $view);
        $this->assertStringContainsString("route('product.list')", $view);
        $this->assertStringContainsString('aria-controls="pharma-mobile-menu"', $view);
    }

    #[Test]
    public function pharma_footer_has_no_purchase_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/footer.blade.php'));
        $this->assertStringNotContainsString('addToCart', $view);
        $this->assertStringContainsString("route('product.list')", $view);
    }
}
