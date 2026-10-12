<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaBrandingCardContractTest extends TestCase
{
    #[Test]
    public function pharma_branding_uses_site_settings_without_hardcoded_company_identity(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/footer.blade.php'));

        $this->assertStringContainsString("get('site_name', 'Pharma')", $header);
        $this->assertStringContainsString("get('site_name', 'Pharma')", $footer);
        $this->assertStringContainsString('max-w-[1440px]', $header);
        $this->assertStringContainsString('max-w-[1440px]', $footer);
        $this->assertStringNotContainsString('FlexBiz', $header);
        $this->assertStringNotContainsString('FlexBiz', $footer);
    }

    #[Test]
    public function category_and_product_cards_have_accessible_responsive_treatment(): void
    {
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString('sm:grid-cols-2 lg:grid-cols-4', $home);
        $this->assertStringContainsString('hover:-translate-y-1', $home);
        $this->assertStringContainsString('focus-visible:outline', $home);
        $this->assertStringContainsString('@if($product->image)', $home);
        $this->assertStringContainsString('Chưa có ảnh sản phẩm', $home);
        $this->assertStringContainsString('route(\'product.detail\', $product->slug)', $home);
    }
}
