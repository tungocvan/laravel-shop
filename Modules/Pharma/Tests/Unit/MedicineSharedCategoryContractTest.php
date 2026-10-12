<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Category\Models\Category;
use Modules\Pharma\Models\Medicine;
use PHPUnit\Framework\TestCase;

class MedicineSharedCategoryContractTest extends TestCase
{
    public function test_medicine_uses_shared_category_pivot_scoped_to_medicine_type(): void
    {
        $relation = (new Medicine())->categories();

        $this->assertSame(Category::class, get_class($relation->getRelated()));
        $this->assertSame('pharma_medicine_category_links', $relation->getTable());
        $this->assertSame('medicine_id', $relation->getForeignPivotKeyName());
        $this->assertSame('category_id', $relation->getRelatedPivotKeyName());
        $wheres = $relation->getQuery()->getQuery()->wheres;
        $this->assertContains('categories.type', array_column($wheres, 'column'));
        $this->assertContains('medicine', array_column($wheres, 'value'));
    }

    public function test_legacy_medicine_categories_relationship_is_preserved(): void
    {
        $relation = (new Medicine())->medicineCategories();

        $this->assertSame('pharma_medicine_category_medicine', $relation->getTable());
    }
}
