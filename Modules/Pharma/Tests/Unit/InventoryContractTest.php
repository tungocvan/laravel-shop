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
        foreach (['issue-form.blade.php','documents.blade.php','receipt-show.blade.php','receipt-edit.blade.php','issue-show.blade.php','issue-edit.blade.php'] as $file) {
            $candidate=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/'.$file));
            token_get_all(Blade::compileString($candidate), TOKEN_PARSE);
        }
        $this->addToAssertionCount(7);
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
        $this->assertStringContainsString('Export toàn bộ', $index);
        $this->assertStringContainsString('Xuất Excel đã chọn', $index);
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
        $this->assertStringContainsString('expiredInventoryValue', $controller);
        $this->assertStringContainsString("get(['medicine_id','quantity_on_hand','expiry_date'])", $controller);
        $this->assertStringContainsString("expiry_date->lt(now()->startOfDay())", $controller);
        $this->assertStringContainsString('Giá trị hàng đã hết hạn', $index);
        $this->assertStringContainsString('number_format($expiredInventoryValue', $index);
        $this->assertStringContainsString('md:grid-cols-3', $index);
        $this->assertStringContainsString("in_array((int)\$request->input('per_page',25),[25,50,100],true)", $controller);
        $this->assertStringContainsString('name="per_page"', $index);
        $this->assertStringContainsString('inventory-select-all', $index);
        $this->assertStringContainsString('inventory-row-checkbox', $index);
        $this->assertStringContainsString('Xuất Excel đã chọn', $index);
        $this->assertStringContainsString("name('export-selected')", $routes);
        $this->assertStringContainsString("name('balances.update')", $routes);
        $this->assertStringContainsString("name('balances.destroy')", $routes);
        $this->assertStringContainsString('function exportSelected', $controller);
        $this->assertStringContainsString("'ids'=>'required|array|min:1|max:100'", $controller);
        $this->assertStringContainsString('function updateBalance', $controller);
        $this->assertStringContainsString('function destroyBalance', $controller);
        $this->assertStringContainsString('Không thể xóa lô đã có lịch sử giao dịch kho.', $controller);
        $this->assertStringContainsString('Import / Export Excel', $index);
        $this->assertStringContainsString('<dialog id="inventory-export-modal"', $index);
        $this->assertStringContainsString("showModal()", $index);
        $this->assertStringContainsString('Lưu thay đổi', $index);
        $this->assertStringContainsString('Xác nhận xóa', $index);
        $receipt=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-form.blade.php'));
        $this->assertStringContainsString('<x-select-search id="receipt-supplier"', $receipt);
        $this->assertStringContainsString('Tìm nhà cung cấp...', $receipt);
        $this->assertStringContainsString('Tên thuốc / Mã thuốc', $receipt);
        $this->assertStringContainsString('Giá nhập chưa VAT', $receipt);
        $this->assertStringContainsString("placeholder: 'Tìm mã hoặc tên thuốc...'", $receipt);
        $this->assertStringContainsString("new TomSelect(select", $receipt);
        $this->assertStringContainsString("Partner::query()->withPartnerType('supplier')->where('status','active')", $controller);
        $this->assertStringContainsString("'partners'=>\$partners", $controller);
        $searchSelect=file_get_contents(base_path('resources/views/components/select-search.blade.php'));
        $this->assertStringNotContainsString('@this.set(', $searchSelect);
        $this->assertStringContainsString('this.$wire.set(config.model, value, false);', $searchSelect);
        $issueForm=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $this->assertStringContainsString("name('receipts.index')", $routes);
        $this->assertStringContainsString("name('issues.index')", $routes);
        $this->assertStringContainsString('function receipts(', $controller);
        $this->assertStringContainsString('function issues(', $controller);
        $this->assertStringContainsString("nextDocumentNumber(InventoryReceipt::class,'PN')", $controller);
        $this->assertStringContainsString("nextDocumentNumber(InventoryIssue::class,'PX')", $controller);
        $this->assertStringContainsString("lockForUpdate()", $controller);
        $this->assertStringContainsString("format('ymd')", $controller);
        $this->assertStringContainsString('Xác nhận ghi sổ', $index);
        $this->assertStringNotContainsString("return confirm('Ghi sổ", $index);
        $this->assertStringContainsString('Xem tất cả →', $index);
        $this->assertStringContainsString('Tên thuốc / Mã thuốc', $issueForm);
        $this->assertStringContainsString('Số lô · Hạn dùng · Tồn khả dụng', $issueForm);
        $this->assertStringContainsString('<x-select-search id="issue-recipient"', $issueForm);
        $this->assertStringContainsString("withPartnerType('customer')", $controller);
        $this->assertStringContainsString("whereDate('expiry_date','>=',now()->toDateString())", $controller);
        $this->assertStringContainsString("onChange:(value)=>fillLots(row,value)", $issueForm);
        $this->assertStringContainsString("medicineSelect.addEventListener('change'", $issueForm);
        $this->assertStringContainsString('Không còn lô khả dụng', $issueForm);
        $issueShow=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $issueEdit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));
        $this->assertStringContainsString("redirect()->route('admin.pharma.inventory.issues.index')", $controller);
        $this->assertStringContainsString("name('issues.show')", $routes);
        $this->assertStringContainsString("name('issues.edit')", $routes);
        $this->assertStringContainsString("name('issues.update')", $routes);
        $this->assertStringContainsString("name('issues.destroy')", $routes);
        $this->assertStringContainsString("name('issues.revert')", $routes);
        $this->assertStringContainsString("name('issues.export')", $routes);
        $this->assertStringContainsString('function exportIssues', $controller);
        $this->assertStringContainsString("'unit_price'=>(float)\$cost->average_cost_price", $controller);
        $this->assertStringContainsString('public function revertIssue', $service);
        $this->assertStringContainsString("'type'=>'issue_reversal'", $service);
        $this->assertStringContainsString('Tổng giá trị', $documents);
        $this->assertStringNotContainsString('Tổng SL', $documents);
        $this->assertStringContainsString('Export Excel', $documents);
        $this->assertStringContainsString('Thành tiền', $issueShow);
        $this->assertStringContainsString('Tổng giá trị', $issueShow);
        $this->assertStringContainsString('<x-select-search id="issue-edit-recipient"', $issueEdit);
        $this->assertStringContainsString("placeholder:'Tìm mã hoặc tên thuốc...'", $issueForm);
        $this->assertStringContainsString('Chọn lô còn tồn', $issueForm);
        $this->assertStringContainsString('Xác nhận ghi sổ', $documents);
        $this->assertStringContainsString("request('per_page',25)", $documents);
        $this->assertStringContainsString('[25,50,100] as $size', $documents);
        $receiptForm=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-form.blade.php'));
        $receiptShow=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-show.blade.php'));
        $receiptEdit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-edit.blade.php'));
        $this->assertStringContainsString("name('receipts.show')", $routes);
        $this->assertStringContainsString("name('receipts.edit')", $routes);
        $this->assertStringContainsString("name('receipts.update')", $routes);
        $this->assertStringContainsString("name('receipts.destroy')", $routes);
        $this->assertStringContainsString("'supplier_name'=>'required|string|max:255'", $controller);
        $this->assertStringContainsString("redirect()->route('admin.pharma.inventory.receipts.index')", $controller);
        $this->assertStringContainsString('function showReceipt', $controller);
        $this->assertStringContainsString('function editReceipt', $controller);
        $this->assertStringContainsString('function updateReceipt', $controller);
        $this->assertStringContainsString('function destroyReceipt', $controller);
        $this->assertStringContainsString('Chỉ phiếu nhập nháp mới được xóa.', $controller);
        $this->assertStringContainsString('quantity * unit_price_ex_vat', $controller);
        $this->assertStringContainsString('Tổng giá trị', $documents);
        $this->assertStringContainsString('Xác nhận xóa', $documents);
        $this->assertStringContainsString('Thành tiền', $receiptShow);
        $this->assertStringContainsString('Tổng giá trị', $receiptShow);
        $this->assertStringContainsString('chỉ cập nhật thông tin chứng từ', $receiptEdit);
        $this->assertStringContainsString('Nhà cung cấp *', $receiptForm);
        $moduleConfig=file_get_contents(base_path('Modules/Pharma/config/module.php'));
        $this->assertStringContainsString("'delete_pharma'", $moduleConfig);
        $this->assertStringContainsString("can:delete_pharma", $routes);
        $this->assertStringContainsString("name('receipts.revert')", $routes);
        $this->assertStringContainsString('function revertReceipt', $controller);
        $this->assertStringContainsString('public function revertReceipt', $service);
        $this->assertStringContainsString("whereIn('type',['receipt','receipt_reversal'])", $service);
        $this->assertStringContainsString("havingRaw('SUM(quantity_delta) > 0')", $service);
        $this->assertStringContainsString("'type'=>'receipt_reversal'", $service);
        $this->assertStringContainsString("'status'=>InventoryReceipt::DRAFT,'posted_by'=>null,'posted_at'=>null", $service);
        $this->assertStringContainsString('$balance->delete()', $service);
        $this->assertStringContainsString("@can('delete_pharma')", $documents);
        $this->assertStringContainsString('Hoàn tác ghi sổ', $documents);
        $this->assertStringContainsString('Hoàn tác ghi sổ?', $documents);
        $this->assertStringContainsString('>Hoàn tác ghi sổ</button>', $documents);
        $this->assertStringContainsString('m-auto w-[calc(100%-2rem)] max-w-lg', $documents);
        $this->assertStringContainsString('bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65', $documents);
        $this->assertStringContainsString('backdrop:backdrop-blur-[3px]', $documents);
        $this->assertStringContainsString('aria-label="Đóng"', $documents);
        $this->assertStringContainsString('Kiểm tra tồn kho:', $documents);
        $this->assertStringContainsString('flex flex-col-reverse gap-2 border-t', $documents);
        $this->assertStringContainsString('m-auto w-[calc(100%-2rem)] max-w-lg', $index);
        $this->assertLessThan(strpos($index,'Import / Export Excel'),strpos($index,'Phiếu nhập gần đây'));
    }
}