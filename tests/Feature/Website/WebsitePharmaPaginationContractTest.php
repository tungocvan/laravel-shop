<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaPaginationContractTest extends TestCase
{
    #[Test]
    public function catalog_has_vietnamese_responsive_pagination(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/products/pharma-catalog.blade.php'));

        $this->assertStringContainsString('Phân trang sản phẩm dược phẩm', $view);
        $this->assertStringContainsString('trong <strong class="text-slate-900">{{ $products->total() }}</strong> sản phẩm', $view);
        $this->assertStringContainsString('aria-current="page"', $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringNotContainsString('$products->links()', $view);
    }

    #[Test]
    public function paginator_uses_livewire_navigation_and_respects_bounds(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/products/pharma-catalog.blade.php'));

        $this->assertStringContainsString('wire:click="previousPage"', $view);
        $this->assertStringContainsString('wire:click="nextPage"', $view);
        $this->assertStringContainsString('wire:click="gotoPage(', $view);
        $this->assertStringContainsString('@disabled($products->onFirstPage())', $view);
        $this->assertStringContainsString('@disabled(!$products->hasMorePages())', $view);
        $this->assertStringContainsString('@if($products->hasPages())', $view);
    }

    #[Test]
    public function catalog_keeps_server_side_pagination_and_resets_after_filtering(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Products/PharmaCatalog.php'));

        $this->assertStringContainsString('->paginate(12)', $component);
        $this->assertStringContainsString('public function updatedSearch()', $component);
        $this->assertStringContainsString('public function updatedCategory()', $component);
        $this->assertStringContainsString('$this->resetPage()', $component);
    }
}
