<?php

namespace Tests\Unit\Category;

use Modules\Category\Services\CategoryService;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    public function test_it_normalizes_an_explicit_slug(): void
    {
        $service = new CategoryService;

        $this->assertSame(
            'duoc-pham-moi',
            $service->normalizeSlug('  Dược phẩm Mới  ', 'Tên không được dùng')
        );
    }

    public function test_it_falls_back_to_the_category_name(): void
    {
        $service = new CategoryService;

        $this->assertSame(
            'thuc-pham-chuc-nang',
            $service->normalizeSlug(null, 'Thực phẩm chức năng')
        );
    }
}
