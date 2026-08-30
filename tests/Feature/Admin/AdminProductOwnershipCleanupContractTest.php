<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminProductOwnershipCleanupContractTest extends TestCase
{
    public function test_product_admin_routes_are_owned_by_product_module(): void
    {
        foreach (['admin.products.index', 'admin.products.create', 'admin.products.edit'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route [{$name}].");
            $this->assertStringContainsString('Modules\\Product\\Http\\Controllers\\ProductController', $route->getActionName());
        }

        $commission = app('router')->getRoutes()->getByName('admin.products.commissions');
        $this->assertNotNull($commission, 'Missing product commission route.');
        $this->assertStringContainsString('Modules\\Product\\Http\\Controllers\\ProductCommissionController', $commission->getActionName());
    }

    public function test_canonical_product_pages_mount_product_livewire_components(): void
    {
        $index = file_get_contents(base_path('Modules/Product/resources/views/pages/products/index.blade.php'));
        $create = file_get_contents(base_path('Modules/Product/resources/views/pages/products/create.blade.php'));
        $edit = file_get_contents(base_path('Modules/Product/resources/views/pages/products/edit.blade.php'));

        $this->assertStringContainsString("@livewire('product.products.product-table')", $index);
        $this->assertStringContainsString("@livewire('product.products.product-form')", $create);
        $this->assertStringContainsString("@livewire('product.products.product-form'", $edit);
    }

    public function test_product_index_keeps_single_workspace_heading_and_white_indigo_pagination_scope(): void
    {
        $index = file_get_contents(base_path('Modules/Product/resources/views/pages/products/index.blade.php'));
        $table = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringNotContainsString('<h1 class="text-2xl font-bold mb-6 text-gray-800">Danh sách sản phẩm</h1>', $index);
        $this->assertStringContainsString('product-admin-workspace', $index);
        $this->assertStringContainsString('nav[role="navigation"] a,', $index);
        $this->assertStringContainsString('background-color: white !important;', $index);
        $this->assertStringContainsString('nav[role="navigation"] [aria-current="page"] > span', $index);
        $this->assertStringContainsString('rgb(79 70 229)', $index);
        $this->assertSame(1, substr_count($table, '>Danh sách sản phẩm</h1>'));
    }

    public function test_product_category_selector_is_recursive_collapsed_and_edit_aware(): void
    {
        $form = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductForm.php'));
        $selector = file_get_contents(base_path('Modules/Admin/resources/views/components/category-select.blade.php'));
        $row = file_get_contents(base_path('Modules/Admin/resources/views/components/category-select-row.blade.php'));

        $this->assertStringContainsString("with('childrenRecursive')", $form);
        $this->assertStringContainsString("@include('Admin::components.category-select-row'", $selector);
        $this->assertStringContainsString('x-data="{ open: false }"', $row);
        $this->assertStringContainsString("open ? '−' : '+'", $row);
        $this->assertStringContainsString('data-category-children', $row);
        $this->assertStringContainsString('input[type=checkbox]:checked', $row);
        $this->assertStringContainsString('x-show="open"', $row);
        $this->assertStringContainsString("'depth' => $depth + 1", $row);
    }

    public function test_legacy_admin_product_runtime_remains_absent(): void
    {
        $legacyFiles = [
            'Modules/Admin/Livewire/Products/ProductForm.php',
            'Modules/Admin/Livewire/Products/ProductTable.php',
            'Modules/Admin/resources/views/livewire/products/product-form.blade.php',
            'Modules/Admin/resources/views/livewire/products/product-table.blade.php',
            'Modules/Admin/Exports/ProductsExport.php',
            'Modules/Admin/Imports/ProductsImport.php',
        ];

        foreach ($legacyFiles as $file) {
            $this->assertFileDoesNotExist(base_path($file), "Legacy Admin Product runtime returned: {$file}");
        }
    }

    public function test_canonical_product_runtime_and_import_export_exist(): void
    {
        foreach ([
            'Modules/Product/Livewire/Products/ProductForm.php',
            'Modules/Product/Livewire/Products/ProductTable.php',
            'Modules/Product/resources/views/livewire/products/product-form.blade.php',
            'Modules/Product/resources/views/livewire/products/product-table.blade.php',
            'Modules/Product/Exports/ProductsExport.php',
            'Modules/Product/Imports/ProductsImport.php',
        ] as $file) {
            $this->assertFileExists(base_path($file), "Missing canonical Product runtime: {$file}");
        }
    }

    public function test_product_livewire_actions_keep_capability_authorization(): void
    {
        $form = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductForm.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));

        $this->assertStringContainsString("authorizeAdmin('create_product')", $form);
        $this->assertStringContainsString("authorizeAdmin('edit_product')", $form);
        $this->assertStringContainsString("authorizeAdmin('edit_product')", $table);
        $this->assertStringContainsString("authorizeAdmin('create_product')", $table);
        $this->assertStringContainsString("authorizeAdmin('delete_product')", $table);
        $this->assertStringContainsString("authorizeAdmin('view_product')", $table);
    }

    public function test_product_commission_remains_canonical_but_is_not_redesigned_by_this_slice(): void
    {
        $view = file_get_contents(base_path('Modules/Product/resources/views/pages/affiliate/product-commissions.blade.php'));

        $this->assertStringContainsString("@livewire('product.products.product-form'", $view);
    }
}
