<?php

namespace Tests\Feature\Inventory;

use Modules\Inventory\Support\DecimalQuantity;
use Tests\TestCase;

class InventoryQuantityAndLotContractTest extends TestCase
{
    public function test_decimal_quantity_uses_exact_six_decimal_string_arithmetic_across_schema_range(): void
    {
        $this->assertSame('0.300000', DecimalQuantity::add('0.100000', '0.200000'));
        $this->assertSame('99999999999999.999999', DecimalQuantity::normalize('99999999999999.999999'));
        $this->assertSame('99999999999999.000000', DecimalQuantity::add('99999999999998.500000', '0.500000'));
        $this->assertSame('-3.250000', DecimalQuantity::negate('3.250000'));
        $this->assertTrue(DecimalQuantity::hasFractionalPart('1.000001'));
        $this->assertFalse(DecimalQuantity::hasFractionalPart('1.000000'));
    }

    public function test_lot_service_enforces_lot_and_expiry_tracking_as_first_class_invariants(): void
    {
        $source = file_get_contents(base_path('Modules/Inventory/Services/LotService.php'));

        $this->assertStringContainsString('Lot number is required for lot-tracked inventory items.', $source);
        $this->assertStringContainsString('Expiry date is required for expiry-tracked inventory items.', $source);
        $this->assertStringContainsString("hash('sha256'", $source);
        $this->assertStringContainsString('inventory_item_id', $source);
        $this->assertStringContainsString('expiry_date', $source);
    }

    public function test_all_core_posting_services_enforce_base_uom_and_fractional_policy(): void
    {
        foreach ([
            'ReceiptPostingService.php',
            'IssuePostingService.php',
            'TransferPostingService.php',
            'StocktakePostingService.php',
        ] as $file) {
            $source = file_get_contents(base_path('Modules/Inventory/Services/'.$file));

            $this->assertStringContainsString('base UOM must match the inventory item base UOM.', $source, $file);
            $this->assertStringContainsString('allow_fractional_quantity', $source, $file);
        }
    }
}
