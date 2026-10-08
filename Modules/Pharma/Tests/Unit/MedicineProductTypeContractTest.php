<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Models\Medicine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineProductTypeContractTest extends TestCase
{
    #[Test]
    public function product_types_are_stable_and_default_to_modern_medicine(): void
    {
        $this->assertSame([
            'tan_duoc' => 'Tân dược',
            'dong_duoc' => 'Đông dược',
            'thuc_pham_chuc_nang' => 'Thực phẩm chức năng',
        ], Medicine::productTypeOptions());

        $medicine = new Medicine();
        $this->assertSame(Medicine::PRODUCT_TYPE_MODERN, $medicine->product_type);
        $this->assertContains('product_type', $medicine->getFillable());
    }

    #[Test]
    public function admin_catalog_and_editor_expose_the_same_product_type(): void
    {
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Form.php'));
        $editor = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/form.blade.php'));
        $catalog = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_10_08_120000_add_product_type_to_pharma_medicines_table.php'));

        $this->assertStringContainsString("Rule::in(array_keys(Medicine::productTypeOptions()))", $form);
        $this->assertStringContainsString('wire:model="product_type"', $editor);
        $this->assertStringContainsString('Loại sản phẩm', $catalog);
        $this->assertStringContainsString("->default('tan_duoc')", $migration);
    }
}
