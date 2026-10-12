<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCategoryFoundationTest extends TestCase
{
    #[Test]
    public function category_storage_is_isolated_from_website_categories(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_10_12_150000_create_pharma_medicine_categories_tables.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/MedicineCategory.php'));

        $this->assertStringContainsString("Schema::create('pharma_medicine_categories'", $migration);
        $this->assertStringContainsString("Schema::create('pharma_medicine_category_medicine'", $migration);
        $this->assertStringContainsString("->unique()", $migration);
        $this->assertStringContainsString("restrictOnDelete()", $migration);
        $this->assertStringContainsString("protected \$table = 'pharma_medicine_categories'", $model);
        $this->assertStringContainsString('function medicines()', $model);
    }
}
