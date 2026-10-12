<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaProductCategorySlugExcelContractTest extends TestCase
{
    public function test_export_uses_medicine_category_slugs(): void
    {
        $source = file_get_contents(base_path('Modules/Product/Exports/PharmaProductsExport.php'));
        $this->assertStringContainsString("'Danh mục (Slugs)'", $source);
        $this->assertStringContainsString("where('type', 'medicine')->pluck('slug')", $source);
    }

    public function test_import_supports_legacy_ids_and_creates_only_medicine_categories_in_transaction(): void
    {
        $source = file_get_contents(base_path('Modules/Product/Services/PharmaProductExcelImportService.php'));
        $this->assertStringContainsString("'Danh mục (IDs)'", $source);
        $this->assertStringContainsString("where('type', 'medicine')", $source);
        $this->assertStringContainsString("DB::transaction(function () use (\$sheets)", $source);
        $this->assertStringContainsString("'type' => 'medicine'", $source);
        $this->assertStringContainsString("'parent_id' => null", $source);
        $this->assertStringContainsString("categories()->sync(\$categoryIds)", $source);
    }

    public function test_preview_discloses_new_category_slugs(): void
    {
        $source = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));
        $this->assertStringContainsString("Danh mục mới sẽ tạo", $source);
        $this->assertStringContainsString("\$previewRow['new_slugs']", $source);
    }
}
