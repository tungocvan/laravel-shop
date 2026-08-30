<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminCategoryOwnershipCleanupContractTest extends TestCase
{
    public function test_category_admin_routes_are_owned_by_category_module(): void
    {
        $routes = app('router')->getRoutes();

        $index = $routes->getByName('admin.category.index');
        $create = $routes->getByName('admin.category.create');
        $edit = $routes->getByName('admin.category.edit');

        $this->assertNotNull($index);
        $this->assertNotNull($create);
        $this->assertNotNull($edit);

        $this->assertSame(
            'Modules\\Category\\Http\\Controllers\\CategoryController@index',
            $index->getActionName()
        );
        $this->assertSame(
            'Modules\\Category\\Http\\Controllers\\CategoryController@create',
            $create->getActionName()
        );
        $this->assertSame(
            'Modules\\Category\\Http\\Controllers\\CategoryController@edit',
            $edit->getActionName()
        );
    }

    public function test_legacy_admin_category_runtime_copies_are_removed(): void
    {
        $legacyFiles = [
            'Modules/Admin/Http/Controllers/CategoryController.php',
            'Modules/Admin/Livewire/Categories/CategoryForm.php',
            'Modules/Admin/Livewire/Categories/CategoryTable.php',
            'Modules/Admin/resources/views/pages/categories/index.blade.php',
            'Modules/Admin/resources/views/pages/categories/create.blade.php',
            'Modules/Admin/resources/views/pages/categories/edit.blade.php',
            'Modules/Admin/resources/views/livewire/categories/category-form.blade.php',
            'Modules/Admin/resources/views/livewire/categories/category-table.blade.php',
        ];

        foreach ($legacyFiles as $file) {
            $this->assertFileDoesNotExist(base_path($file), $file.' must stay outside the Admin shell.');
        }
    }

    public function test_category_module_keeps_the_canonical_admin_workspace(): void
    {
        $canonicalFiles = [
            'Modules/Category/Http/Controllers/CategoryController.php',
            'Modules/Category/Livewire/Categories/CategoryForm.php',
            'Modules/Category/Livewire/Categories/CategoryTable.php',
            'Modules/Category/resources/views/pages/categories/index.blade.php',
            'Modules/Category/resources/views/pages/categories/create.blade.php',
            'Modules/Category/resources/views/pages/categories/edit.blade.php',
        ];

        foreach ($canonicalFiles as $file) {
            $this->assertFileExists(base_path($file), $file.' is part of the canonical Category workspace.');
        }

        $index = file_get_contents(base_path('Modules/Category/resources/views/pages/categories/index.blade.php'));
        $create = file_get_contents(base_path('Modules/Category/resources/views/pages/categories/create.blade.php'));
        $edit = file_get_contents(base_path('Modules/Category/resources/views/pages/categories/edit.blade.php'));

        $this->assertStringContainsString("@livewire('category.categories.category-table')", $index);
        $this->assertStringContainsString("@livewire('category.categories.category-form')", $create);
        $this->assertStringContainsString("@livewire('category.categories.category-form', ['id' => $id])", $edit);
    }
}
