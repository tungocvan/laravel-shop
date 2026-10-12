<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaFullWidthLayoutContractTest extends TestCase
{
    #[Test]
    public function pharma_main_shell_is_not_limited_by_default_website_container(): void
    {
        $styles = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/presentation-styles.blade.php'));

        $this->assertStringContainsString('WebsiteTemplateService::class)->isPharma()', $styles);
        $this->assertStringContainsString('max-width: none;', $styles);
        $this->assertStringContainsString('.website-main-shell { padding: 0; }', $styles);
        $this->assertStringContainsString('max-width: {{ $layoutMaxWidth }};', $styles);
    }

    #[Test]
    public function pharma_sections_keep_independent_centered_content_width(): void
    {
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));
        $this->assertStringContainsString('mx-auto grid w-full max-w-[1440px]', $home);
        $this->assertStringContainsString('mx-auto w-full max-w-[1440px]', $home);
        $this->assertStringContainsString('bg-gradient-to-br from-emerald-950', $home);
    }

    #[Test]
    public function default_template_still_uses_shared_website_layout(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/index.blade.php'));

        $this->assertStringContainsString('class="website-main-shell flex-grow"', $layout);
        $this->assertStringContainsString("@livewire('website.home.home-list')", $home);
        $this->assertStringContainsString("Website::pages.home.pharma", $home);
    }
}
