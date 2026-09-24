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
        $this->assertStringContainsString("if(!\$warehouse->opening_cutoff_at) \$warehouse->update(['opening_cutoff_at'=>now()])", $service);
        $this->assertStringContainsString('assertDocumentDateAfterOpeningCutoff', $service);
        $this->assertStringContainsString('Ngày phiếu nhập', $service);
        $this->assertStringContainsString('Ngày phiếu xuất', $service);
        $this->assertStringContainsString("'opening_cutoff_at'=>'datetime'", $warehouse);
    }

    public function test_period_summary_uses_ledger_movements(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryMovementSummaryService.php'));

        $this->assertStringContainsString("where('created_at','<',\$from)", $service);
        $this->assertStringContainsString("whereBetween('created_at',[\$from,\$to])", $service);
        $this->assertStringContainsString("type='receipt_reversal' THEN quantity_delta", $service);
        $this->assertStringContainsString("type='issue_reversal' THEN -quantity_delta", $service);
        $this->assertStringContainsString("COALESCE(pre.opening_quantity,0)+COALESCE(mov.opening_import,0) as period_opening", $service);
        $this->assertStringContainsString('period_closing', $service);
    }

    public function test_inventory_ui_exposes_period_filters_and_xnt_columns(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/movements.blade.php'));

        $this->assertStringContainsString('public function movements(Request $request, InventoryService $inventory, InventoryMovementSummaryService $movementSummary): View', $controller);
        $this->assertStringContainsString("request->input('from')", $controller);
        $this->assertStringContainsString("request->input('to')", $controller);
        $this->assertStringContainsString('Xuất – Nhập – Tồn', $view);
        $this->assertStringContainsString('Mốc bắt đầu sổ kho', $view);
        $this->assertStringContainsString('name="from"', $view);
        $this->assertStringContainsString('name="to"', $view);
        $this->assertStringContainsString('Tồn đầu kỳ', $view);
        $this->assertStringContainsString('Nhập trong kỳ', $view);
        $this->assertStringContainsString('Xuất trong kỳ', $view);
        $this->assertStringContainsString('Tồn cuối kỳ', $view);
        $this->assertStringContainsString('inventory-movement-medicine', $view);
        $this->assertStringContainsString('movement_medicine_id', $view);
        $this->assertStringContainsString('movement-select-all', $view);
        $this->assertStringContainsString('data-movement-check', $view);
        $this->assertStringContainsString('Export theo bộ lọc', $view);
        $this->assertStringContainsString('Export đã chọn', $view);
        $this->assertStringContainsString('Import tồn đầu kỳ', $view);
    }

    public function test_period_export_is_filter_and_selection_aware(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryMovementSummaryService.php'));

        $this->assertStringContainsString("Route::get('/movements/export'", $routes);
        $this->assertStringContainsString('exportMovements', $controller);
        $this->assertStringContainsString("'ids'=>'nullable|array|max:500'", $controller);
        $this->assertStringContainsString("'movement_medicine_id'=>'nullable|integer|exists:pharma_medicines,id'", $controller);
        $this->assertStringContainsString('when($medicineId', $service);
        $this->assertStringContainsString('when($balanceIds', $service);
    }
    public function test_current_stock_and_period_movements_are_separate_workspaces(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $inventory=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));
        $movements=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/movements.blade.php'));
        $opening=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/opening-form.blade.php'));

        $this->assertStringContainsString("Route::get('/movements', [InventoryController::class, 'movements'])->name('movements.index')", $routes);
        $this->assertStringContainsString("return view('Pharma::pages.inventory.movements'", $controller);
        $this->assertStringContainsString("route('admin.pharma.inventory.movements.index')", $inventory);
        $this->assertStringNotContainsString('movement-select-all', $inventory);
        $this->assertStringNotContainsString('Phiếu nhập gần đây', $inventory);
        $this->assertStringNotContainsString('Phiếu xuất gần đây', $inventory);
        $this->assertStringContainsString('movement-select-all', $movements);
        $this->assertStringContainsString('Import hàng loạt', $opening);
        $this->assertStringContainsString("route('admin.pharma.inventory.opening.import')", $opening);
    }

}
