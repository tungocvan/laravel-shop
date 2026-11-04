<?php

namespace Modules\ClientPortal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\ClientPortal\Applications\Pharma\Http\Controllers\PharmaInventoryExcelProfileController;

final class PharmaInventoryExcelProfileContractTest extends TestCase
{
    public function test_stock_and_movement_columns_are_separated(): void
    {
        $stock = PharmaInventoryExcelProfileController::allowed('stock', true);
        $movements = PharmaInventoryExcelProfileController::allowed('movements', true);
        self::assertArrayHasKey('stt', $stock);
        self::assertArrayHasKey('unit', $stock);
        self::assertArrayHasKey('average_cost_price', $stock);
        self::assertArrayHasKey('inventory_value', $stock);
        self::assertArrayHasKey('quantity_on_hand', $stock);
        self::assertArrayNotHasKey('period_in', $stock);
        self::assertArrayHasKey('stt', $movements);
        self::assertArrayHasKey('unit', $movements);
        self::assertSame('STT', $movements['stt']);
        self::assertSame('Đơn vị tính', $movements['unit']);
        self::assertArrayHasKey('period_in', $movements);
        self::assertArrayNotHasKey('quantity_on_hand', $movements);
    }

    public function test_cost_columns_are_hidden_without_permission(): void
    {
        $stock = PharmaInventoryExcelProfileController::allowed('stock', false);
        self::assertArrayNotHasKey('average_cost_price', $stock);
        self::assertArrayNotHasKey('inventory_value', $stock);
    }

    public function test_profile_scopes_are_isolated(): void
    {
        self::assertNotSame(
            PharmaInventoryExcelProfileController::guard('stock'),
            PharmaInventoryExcelProfileController::guard('movements')
        );
    }
}
