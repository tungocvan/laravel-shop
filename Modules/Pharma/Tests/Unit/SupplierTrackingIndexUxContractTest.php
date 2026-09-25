<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierTrackingIndexUxContractTest extends TestCase
{
    #[Test]
    public function supplier_tracking_index_keeps_filters_and_bulk_actions_consistent(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Services/ImportExport.php'));

        $this->assertStringContainsString('function clearSearch()', $component);
        $this->assertStringContainsString('refreshSupplierFilterOptions', $component);
        $this->assertStringContainsString('refreshMedicineFilterOptions', $component);
        $this->assertStringContainsString('wire:click="clearSearch"', $view);
        $this->assertStringContainsString('aria-label="Xóa tìm kiếm"', $view);
        $this->assertStringContainsString('Export Excel đã chọn', $view);
        $this->assertStringContainsString('Đặt lại', $view);
        $this->assertStringContainsString("'purchase_price' => \$purchasePrice", $view);
        $this->assertStringContainsString("\$filters['purchase_price']", $export);
    }
}
