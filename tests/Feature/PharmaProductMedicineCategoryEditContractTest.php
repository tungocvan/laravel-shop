<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaProductMedicineCategoryEditContractTest extends TestCase
{
    public function test_product_form_scopes_category_tree_and_validation_by_source(): void
    {
        $form = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductForm.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-form.blade.php'));

        $this->assertStringContainsString("where('type', \$this->isPharmaLinked ? 'medicine' : 'product')", $form);
        $this->assertStringContainsString("Rule::exists('categories', 'id')->where('type', \$this->isPharmaLinked ? 'medicine' : 'product')", $form);
        $this->assertStringContainsString("'Danh mục thuốc' : 'Danh mục sản phẩm'", $view);
    }

    public function test_product_service_saves_categories_using_source_specific_type(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $this->assertStringContainsString("\$linked ? 'medicine' : 'product'", $service);
        $this->assertStringContainsString("private function normalizeCategoryIds(array \$ids, string \$type = 'product')", $service);
        $this->assertStringContainsString("where('type', \$type)", $service);
        $this->assertStringContainsString('categories()->sync($categoryIds)', $service);
    }

    public function test_standalone_category_csv_route_is_removed(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $this->assertStringNotContainsString('categories-csv', $routes);
    }
}
