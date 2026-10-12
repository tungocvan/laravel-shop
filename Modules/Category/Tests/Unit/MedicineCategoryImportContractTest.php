<?php

namespace Modules\Category\Tests\Unit;

use Tests\TestCase;

class MedicineCategoryImportContractTest extends TestCase
{
    public function test_medicine_import_is_type_scoped_atomic_and_supports_parent_dependencies(): void
    {
        $service = file_get_contents(base_path('Modules/Category/Services/ImportExport.php'));
        $categories = file_get_contents(base_path('Modules/Category/Services/CategoryService.php'));
        $migration = file_get_contents(base_path('Modules/Category/database/migrations/2026_10_12_170000_scope_category_slug_uniqueness_by_type.php'));
        $csv = file_get_contents(base_path('docs/modules/Pharma/pharma-medicine-categories-import.csv'));

        $this->assertStringContainsString("->where('type', \$data['type'])", $categories);
        $this->assertStringContainsString("->unique(['type', 'slug']", $migration);
        $this->assertStringContainsString('DB::beginTransaction()', $service);
        $this->assertStringContainsString('DB::rollBack()', $service);
        $this->assertStringContainsString('while ($pending !== [])', $service);
        $this->assertStringContainsString('Parent rows may appear after their children', $service);
        $this->assertStringContainsString('"medicine"', $csv);
        $this->assertStringNotContainsString('"product"', $csv);
        $this->assertSame(26, count(array_filter(explode("\n", trim($csv)))));
    }
}
