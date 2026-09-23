<?php
namespace Modules\Pharma\Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class InventoryContractTest extends TestCase
{
    public function test_inventory_is_pharma_owned_and_uses_medicine_master(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_23_110000_create_pharma_inventory_tables.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $this->assertStringContainsString("prefix('inventory')", $routes);
        $this->assertStringContainsString("admin.pharma.inventory", $controller);
        $this->assertStringContainsString("constrained('pharma_medicines')", $migration);
        $this->assertStringContainsString('pharma_inventory_balances', $migration);
        $this->assertStringContainsString('pharma_inventory_transactions', $migration);
        $this->assertStringNotContainsString('Modules\\Inventory', $routes.$controller.$migration);
    }

    public function test_inventory_has_batch_expiry_document_lifecycle_and_negative_stock_guard(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $receipt=file_get_contents(base_path('Modules/Pharma/Models/InventoryReceipt.php'));
        $issue=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssue.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));
        $this->assertStringContainsString("'batch_number'", $service);
        $this->assertStringContainsString("'expiry_date'", $service);
        $this->assertStringContainsString("public const DRAFT='draft'", $receipt);
        $this->assertStringContainsString("public const POSTED='posted'", $issue);
        $this->assertStringContainsString('if ($after < 0)', $service);
        $this->assertStringContainsString('Không đủ tồn', $service);
        $this->assertStringContainsString('Tồn đầu kỳ', $view);
        $this->assertStringContainsString('Sắp hết hạn', $view);
    }

    public function test_inventory_index_blade_compiles_to_valid_php(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));
        $compiled = Blade::compileString($view);

        $this->assertNotEmpty($compiled);
        token_get_all($compiled, TOKEN_PARSE);
        $this->addToAssertionCount(1);
    }

    public function test_inventory_admin_ui_and_permissions_follow_pharma_conventions(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $dashboard=file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));
        $receipt=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-form.blade.php'));
        $this->assertStringContainsString("middleware('can:view_pharma')", $routes);
        $this->assertStringContainsString("middleware('can:create_pharma')", $routes);
        $this->assertStringContainsString("middleware('can:edit_pharma')", $routes);
        $this->assertStringContainsString("route('admin.pharma.inventory.index')", $dashboard);
        $this->assertStringContainsString("@extends('Admin::layouts.master')", $receipt);
        $this->assertStringContainsString('unit_price_ex_vat', $receipt);
        $this->assertStringContainsString("name('opening.template')", $routes);
        $this->assertStringContainsString("name('opening.import')", $routes);
        $this->assertStringContainsString("name('export')", $routes);
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $this->assertStringContainsString('FastExcel', $controller);
        $this->assertStringContainsString('StreamedResponse', $controller);
        $this->assertStringNotContainsString('BinaryFileResponse', $controller);
        $index=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));
        $this->assertStringContainsString("route('admin.pharma.dashboard')", $index);
        $this->assertStringContainsString('Import tồn đầu kỳ', $index);
        $this->assertStringContainsString('Export tồn kho', $index);
        $this->assertStringContainsString('expiry_warning', $index);
        $this->assertStringContainsString('Sắp hết hạn ·', $index);
        $this->assertStringContainsString('Giá vốn NCC TB', $index);
        $this->assertStringContainsString('Giá trị tồn', $index);
        $this->assertStringContainsString("route('admin.pharma.supplier-trackings.index'", $index);
        $this->assertStringContainsString("AVG(cost_price) as average_cost_price", $controller);
        $this->assertStringContainsString("where('status','active')", $controller);
        $this->assertStringContainsString("whereNull('start_date')", $controller);
        $this->assertStringContainsString("whereNull('end_date')", $controller);
        $this->assertStringContainsString("'lt6'", $controller);
        $this->assertStringContainsString("'Gia von NCC trung binh'", $controller);
        $this->assertStringContainsString('cost_status', $index);
        $this->assertStringContainsString('Chưa có giá vốn', $index);
        $this->assertStringContainsString('value_sort', $index);
        $this->assertStringContainsString('Giá trị tồn: lớn nhất', $index);
        $this->assertStringContainsString("leftJoinSub", $controller);
        $this->assertStringContainsString("inventory_value", $controller);
        $this->assertStringContainsString("'unpriced'", $controller);
        $this->assertStringContainsString("number_format((float) \$row->opening_quantity, 0", $index);
        $this->assertStringContainsString("number_format((float) \$row->quantity_on_hand, 0", $index);
        $this->assertStringContainsString('onchange="this.form.submit()"', $index);
        $this->assertStringContainsString('oninput="window.clearTimeout', $index);
        $this->assertStringContainsString('window.setTimeout(() => this.form.submit(), 450)', $index);
        $this->assertStringNotContainsString('x-data=', $index);
        $this->assertStringNotContainsString('@change="submitFilters()"', $index);
        $this->assertStringContainsString('Xóa bộ lọc', $index);
        $this->assertStringContainsString("href=\"{{ route('admin.pharma.inventory.index') }}\"", $index);
        $this->assertStringNotContainsString('>Lọc</button>', $index);
    }
}