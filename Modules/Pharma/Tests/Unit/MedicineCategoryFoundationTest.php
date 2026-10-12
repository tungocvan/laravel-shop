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
    #[Test]
    public function admin_crud_routes_and_guards_are_isolated(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineCategoryController.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/medicine-categories/index.blade.php'));

        $this->assertStringContainsString("prefix('medicine-categories')", $routes);
        $this->assertStringContainsString("middleware('can:view_pharma')", $routes);
        $this->assertStringContainsString("middleware('can:create_pharma')", $routes);
        $this->assertStringContainsString("middleware('can:edit_pharma')", $routes);
        $this->assertStringContainsString("middleware('can:delete_pharma')", $routes);
        $this->assertStringContainsString('function isDescendant(', $controller);
        $this->assertStringContainsString("children()->exists()", $controller);
        $this->assertStringContainsString("medicines()->exists()", $controller);
        $this->assertStringContainsString("@extends('Admin::layouts.master')", $view);
    }

}
