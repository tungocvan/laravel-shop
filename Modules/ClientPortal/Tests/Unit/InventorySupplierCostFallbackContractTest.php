<?php

namespace Modules\ClientPortal\Tests\Unit;

use PHPUnit\Framework\TestCase;

class InventorySupplierCostFallbackContractTest extends TestCase
{
    public function test_inventory_cost_falls_back_to_positive_active_supplier_price(): void
    {
        $source = file_get_contents(__DIR__.'/../../../Pharma/Services/UserInventoryWorkspace.php');

        $this->assertStringContainsString('NULLIF(pharma_inventory_balances.manual_cost_price, 0)', $source);
        $this->assertStringContainsString("->where('cost_price', '>', 0)", $source);
        $this->assertStringContainsString('(float) $row->manual_cost_price > 0', $source);
        $this->assertStringContainsString('(float) $lot->manual_cost_price > 0', $source);
    }

    public function test_unpriced_inventory_has_supplier_cost_diagnostics(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/applications/pharma/inventory.blade.php');

        $this->assertStringContainsString('Kiểm tra giá vốn NCC', $view);
        $this->assertStringContainsString("['cost_status'] === 'unpriced'", $view);
    }
}
