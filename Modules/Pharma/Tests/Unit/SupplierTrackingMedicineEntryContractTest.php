<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierTrackingMedicineEntryContractTest extends TestCase
{
    #[Test]
    public function medicine_catalog_entry_reopens_existing_supplier_condition_and_exposes_return_action(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/SupplierTrackingController.php'));
        $create = file_get_contents(base_path('Modules/Pharma/resources/views/pages/supplier-trackings/create.blade.php'));
        $form = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/form.blade.php'));

        $this->assertStringContainsString("where('medicine_id', \$medicineId)->latest('id')->value('id')", $controller);
        $this->assertStringContainsString("'existingTrackingId'", $controller);
        $this->assertStringContainsString("['id' => \$existingTrackingId, 'medicineId' => \$medicineId]", $create);
        $this->assertStringContainsString("route('admin.pharma.medicines.index')", $form);
        $this->assertStringContainsString('← Danh mục thuốc', $form);
        $this->assertStringContainsString("route('admin.pharma.supplier-trackings.index')", $form);
    }
}
