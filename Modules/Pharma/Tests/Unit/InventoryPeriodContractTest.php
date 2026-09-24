<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class InventoryPeriodContractTest extends TestCase
{
    public function test_opening_cutoff_is_persisted_and_guards_posting(): void
    {
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_140000_add_opening_cutoff_to_inventory_warehouses.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $warehouse=file_get_contents(base_path('Modules/Pharma/Models/InventoryWarehouse.php'));

        $this->assertStringContainsString('opening_cutoff_at', $migration);
        $this->assertStringContainsString("where('type','opening')->min('created_at')", $migration);
        $this->assertStringContainsString("if(!$warehouse->opening_cutoff_at) $warehouse->update(['opening_cutoff_at'=>now()])", $service);
        $this->assertStringContainsString('assertDocumentDateAfterOpeningCutoff', $service);
        $this->assertStringContainsString('Ngày phiếu nhập', $service);
        $this->assertStringContainsString('Ngày phiếu xuất', $service);
        $this->assertStringContainsString("'opening_cutoff_at'=>'datetime'", $warehouse);
    }

    public function test_period_summary_uses_ledger_movements(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryMovementSummaryService.php'));

        $this->assertStringContainsString("where('created_at','<',$from)", $service);
        $this->assertStringContainsString("whereBetween('created_at',[$from,$to])", $service);
        $this->assertStringContainsString("type='receipt_reversal' THEN quantity_delta", $service);
        $this->assertStringContainsString("type='issue_reversal' THEN -quantity_delta", $service);
        $this->assertStringContainsString('period_opening', $service);
        $this->assertStringContainsString('period_closing', $service);
    }

    public function test_inventory_ui_exposes_period_filters_and_xnt_columns(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));

        $this->assertStringContainsString('InventoryMovementSummaryService $movementSummary', $controller);
        $this->assertStringContainsString("request->input('from')", $controller);
        $this->assertStringContainsString("request->input('to')", $controller);
        $this->assertStringContainsString('Xuất – Nhập – Tồn theo kỳ', $view);
        $this->assertStringContainsString('Mốc bắt đầu sổ kho', $view);
        $this->assertStringContainsString('name="from"', $view);
        $this->assertStringContainsString('name="to"', $view);
        $this->assertStringContainsString('Tồn đầu kỳ', $view);
        $this->assertStringContainsString('Nhập trong kỳ', $view);
        $this->assertStringContainsString('Xuất trong kỳ', $view);
        $this->assertStringContainsString('Tồn cuối kỳ', $view);
    }
}
