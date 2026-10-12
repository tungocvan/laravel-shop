<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaMedicineCategoryUiContractTest extends TestCase
{
    public function test_medicine_form_validates_shared_taxonomy_and_renders_multi_select(): void
    {
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Form.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/form.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));

        $this->assertStringContainsString("Rule::exists('categories', 'id')->where('type', 'medicine')", $form);
        $this->assertStringContainsString('wire:model="category_ids"', $view);
        $this->assertStringContainsString("where('type', 'medicine')", $service);
        $this->assertStringContainsString('categories()->sync($categoryIds)', $service);
    }

    public function test_product_admin_filter_scopes_taxonomy_to_source(): void
    {
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));

        $this->assertStringContainsString("flatProductCategoryOptions((string) \$this->source)", $table);
        $this->assertStringContainsString("where('type', \$source === 'pharma' ? 'medicine' : 'product')", $service);
        $this->assertStringContainsString("where('categories.type', \$categoryType)", $service);
        $this->assertStringContainsString("\$this->category_id = '';", $table);
    }
}
