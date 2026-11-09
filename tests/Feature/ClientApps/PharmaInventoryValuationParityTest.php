<?php

namespace Tests\Feature\ClientApps;

use Modules\Pharma\Services\InventoryLotCostResolver;
use Tests\TestCase;

final class PharmaInventoryValuationParityTest extends TestCase
{
    public function test_effective_lot_cost_prioritizes_positive_manual_price(): void
    {
        $resolver = new InventoryLotCostResolver();
        $this->assertSame(90000.0, $resolver->effectiveCost(90000.0, 25000.0));
        $this->assertSame(25000.0, $resolver->effectiveCost(0.0, 25000.0));
        $this->assertSame(25000.0, $resolver->effectiveCost(null, 25000.0));
        $this->assertNull($resolver->effectiveCost(0.0, 0.0));
        $this->assertNull($resolver->effectiveCost(null, null));
    }

    public function test_stock_and_movement_reports_use_same_supplier_cost_query(): void
    {
        $workspace = file_get_contents(base_path('Modules/Pharma/Services/UserInventoryWorkspace.php'));
        $movement = file_get_contents(base_path('Modules/Pharma/Services/InventoryMovementSummaryService.php'));
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/InventoryLotCostResolver.php'));

        $this->assertStringContainsString('costResolver->activeSupplierCostQuery()', $workspace);
        $this->assertStringContainsString('costResolver->activeSupplierCostQuery()', $movement);
        $this->assertStringContainsString('costResolver->effectiveCost(', $workspace);
        $this->assertStringContainsString('costResolver->effectiveCost(', $movement);
        $this->assertStringContainsString('COALESCE(NULLIF(cost_price, 0), NULLIF(import_price, 0))', $resolver);
    }
}
