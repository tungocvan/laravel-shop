<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaProductCategorySlugPreviewContractTest extends TestCase
{
    public function test_preview_exposes_assigned_slugs_for_new_and_legacy_files(): void
    {
        $source = file_get_contents(base_path('Modules/Product/Services/PharmaProductExcelImportService.php'));
        $this->assertStringContainsString("'category_slugs' => \$legacyIds", $source);
        $this->assertStringContainsString("whereIn('id', \$tokens)->pluck('slug')->all()", $source);
        $this->assertStringContainsString("'new_slugs' => \$newSlugs", $source);
    }

    public function test_preview_shows_category_assignments_and_summary_counts(): void
    {
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));
        $this->assertStringContainsString('Danh mục sẽ gán (Slugs)', $view);
        $this->assertStringContainsString("\$previewRow['category_slugs']", $view);
        $this->assertStringContainsString('Slug mới sẽ tạo:', $view);
        $this->assertStringContainsString('Hợp lệ:', $view);
        $this->assertStringContainsString('Có lỗi:', $view);
        $this->assertStringContainsString("->unique()->values()", $view);
    }
}
