<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaPublicTemplateContractTest extends TestCase
{
    #[Test]
    public function publishing_is_explicit_and_separate_from_editor_workspace(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));

        $this->assertStringContainsString("website.public_template", $component);
        $this->assertStringContainsString('function publishTemplate(', $component);
        $this->assertStringContainsString('authorizeAdminPermission', $component);
        $this->assertStringContainsString('wire:click="publishTemplate(', $admin);
        $this->assertStringContainsString('wire:confirm=', $admin);
    }

    #[Test]
    public function public_renderer_reads_only_pharma_keys_and_preserves_default_fallback(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/WebsiteController.php'));
        $renderer = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString("website.public_template", $controller);
        $this->assertStringContainsString("Website::pages.home.pharma", $controller);
        $this->assertStringContainsString("Website::pages.home.index", $controller);
        $this->assertStringContainsString("website.pharma_homepage.order", $renderer);
        $this->assertStringContainsString("website.pharma_homepage.layout", $renderer);
        $this->assertStringContainsString("website.pharma_homepage.theme", $renderer);
        $loader = file_get_contents(base_path('Modules/Website/Livewire/Home/PharmaProductSection.php'));
        $cards = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-product-section.blade.php'));
        $this->assertStringContainsString('PharmaProductSection::class', $renderer);
        $this->assertStringContainsString("whereHas('pharmaLink')", $loader);
        $this->assertStringContainsString("route('product.list', ['category' => \$category->slug])", $renderer);
        $this->assertStringContainsString("route('product.detail', \$product->slug)", $cards);
        $this->assertStringNotContainsString('Flash Sale', $renderer);
    }
}
