<?php

namespace Tests\Unit\Product;

use Modules\Product\Services\ProductService;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function test_it_normalizes_product_slugs(): void
    {
        $service = new ProductService();

        $this->assertSame('san-pham-moi', $service->normalizeSlug(null, 'Sản phẩm mới'));
        $this->assertSame('custom-slug', $service->normalizeSlug('Custom Slug', 'Ignored'));
    }

    public function test_it_allows_only_safe_sort_columns(): void
    {
        $service = new ProductService();

        $this->assertSame('quantity', $service->normalizeSortColumn('quantity'));
        $this->assertSame('created_at', $service->normalizeSortColumn('drop table users'));
    }

    public function test_it_caps_unbounded_pagination(): void
    {
        $service = new ProductService();

        $this->assertSame(100, $service->normalizePerPage('all'));
        $this->assertSame(10, $service->normalizePerPage(999999));
    }
}
