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
        $this->assertStringNotContainsString('if ($after < 0)', $service);
        $this->assertStringContainsString('Không đủ tồn cho lô', $service);
        $this->assertStringContainsString('$balance->quantity_on_hand < (float)$item->quantity', $service);
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
        $opening=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/opening-form.blade.php'));
        $this->assertStringContainsString("route('admin.pharma.dashboard')", $index);
        $this->assertStringContainsString('Import tồn đầu kỳ', $opening);
        $this->assertStringContainsString("route('admin.pharma.inventory.opening.import')", $opening);
        $this->assertStringContainsString('Export tồn kho', $index);
        $this->assertStringContainsString('Xuất Excel đã chọn', $index);
        $this->assertStringContainsString('expiry_warning', $index);
        $this->assertStringContainsString('Sắp hết hạn ·', $index);
        $this->assertStringContainsString('Giá vốn', $index);
        $this->assertStringContainsString('Giá trị tồn', $index);
        $this->assertStringContainsString("AVG(cost_price) as average_cost_price", $controller);
        $this->assertStringContainsString("where('status','active')", $controller);
        $this->assertStringContainsString("whereNull('start_date')", $controller);
        $this->assertStringContainsString("whereNull('end_date')", $controller);
        $this->assertStringContainsString("'lt6'", $controller);
        $this->assertStringContainsString("'Gia von'", $controller);
        $this->assertStringContainsString("'Nguon gia von'", $controller);
        $this->assertStringContainsString('cost_status', $index);
        $this->assertStringContainsString('Chưa có giá vốn', $index);
        $this->assertStringContainsString('value_sort', $index);
        $this->assertStringContainsString('Giá trị tồn: lớn nhất', $index);
        $this->assertStringContainsString("leftJoinSub", $controller);
        $this->assertStringContainsString("inventory_value", $controller);
        $this->assertStringContainsString("'unpriced'", $controller);
        $this->assertStringContainsString("COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) <= 0", $controller);
        $this->assertStringContainsString("COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) > 0", $controller);
        $this->assertStringContainsString("return \$effective === null || \$effective <= 0;", $controller);
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
        $this->assertStringContainsString("get(['medicine_id','quantity_on_hand','expiry_date','manual_cost_price'])", $controller);
        $this->assertStringContainsString("expiry_date->lt(\$today)", $controller);
        $this->assertStringContainsString("expiry_date->gte(\$today)", $controller);
        $this->assertStringContainsString('Hàng hết hạn còn tồn', $index);
        $this->assertStringContainsString('Tổng giá trị tồn, bao gồm cả hàng còn hạn và đã hết hạn.', $index);
        $this->assertStringContainsString('Trong đó còn hạn', $index);
        $this->assertStringContainsString('validInventoryValue', $index);
        $this->assertStringContainsString('validBalanceCount', $index);
        $this->assertStringContainsString("'validInventoryValue','validBalanceCount'", $controller);
        $this->assertStringContainsString('Giá trị hàng cận hạn ≤ 6 tháng', $index);
        $this->assertStringContainsString('Không tính hàng đã hết hạn.', $index);
        $this->assertStringContainsString('nearExpiryInventoryValue', $index);
        $this->assertStringContainsString('nearExpiryBalanceCount', $index);
        $this->assertStringContainsString("'nearExpiryInventoryValue','nearExpiryBalanceCount'", $controller);
        $this->assertStringContainsString("expiry_date->gte(\$today)", $controller);
        $this->assertStringContainsString("expiry_date->lte(\$nearExpiryEnd)", $controller);
        $this->assertStringContainsString("fullUrlWithQuery(['expiry_warning' => 'lt6'", $index);
        $this->assertStringContainsString('number_format($expiredBalanceCount)', $index);
        $this->assertStringContainsString('number_format($expiredInventoryValue', $index);
        $this->assertStringContainsString('md:grid-cols-2 xl:grid-cols-4', $index);
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
        $this->assertStringContainsString('manual_cost_price', $controller);
        $this->assertStringContainsString('pharma_inventory_cost_adjustments', $controller);
        $this->assertStringContainsString('cost_adjustment_reason', $controller);
        $this->assertStringContainsString('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price)', $controller);
        $this->assertStringContainsString('Điều chỉnh thủ công theo lô', $index);
        $this->assertStringContainsString('Nhập 0 để lưu đúng giá vốn 0 đ', $index);
        $this->assertStringContainsString('function destroyBalance', $controller);
        $this->assertStringContainsString('Không thể xóa lô đã có lịch sử giao dịch kho.', $controller);
        $this->assertStringNotContainsString('Import / Export Excel', $index);
        $this->assertStringContainsString('<dialog id="inventory-export-modal"', $index);
        $this->assertStringContainsString('File chỉ chứa các dòng đang được chọn trên trang hiện tại.', $index);
        $this->assertStringContainsString('Export tồn kho', $index);
        $this->assertStringContainsString("route('admin.pharma.inventory.movements.index')", $index);
        $this->assertStringContainsString('Import hàng loạt', $opening);
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
        $this->assertStringContainsString('Xác nhận ghi sổ', $documents);
        $this->assertStringNotContainsString("return confirm('Ghi sổ", $documents);
        $this->assertStringNotContainsString('Phiếu nhập gần đây', $index);
        $this->assertStringNotContainsString('Phiếu xuất gần đây', $index);
        $this->assertStringContainsString('Tên thuốc / Mã thuốc', $issueForm);
        $this->assertStringContainsString('Số lô · Hạn dùng · Tồn khả dụng', $issueForm);
        $this->assertStringContainsString('<x-select-search id="issue-recipient"', $issueForm);
        $this->assertStringContainsString("withPartnerType('customer')", $controller);
        $this->assertStringContainsString("whereDate('expiry_date','>=',now()->toDateString())", $controller);
        $this->assertStringContainsString("onChange:(value)=>{ fillLots(row,value); refreshPrice(row); }", $issueForm);
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
        $this->assertStringContainsString("'items.*.unit_price'=>'required|numeric|min:0'", $controller);
        $this->assertStringContainsString("'unit_price'=>(float)\$item['unit_price']", $controller);
        $this->assertStringContainsString('issueSalePriceCandidates', $controller);
        $this->assertStringContainsString("PriceList::STATUS_ACTIVE", $controller);
        $this->assertStringContainsString("company_sale_price", $controller);
        $this->assertStringContainsString('Đơn giá xuất', $issueForm);
        $this->assertStringContainsString('Giá bán CT', $issueForm);
        $this->assertStringContainsString("data-field=\"unit_price\"", $issueForm);
        $this->assertStringContainsString("value=\"0\"", $issueForm);
        $this->assertStringContainsString('có thể nhập tay', $issueForm);
        $this->assertStringContainsString("candidate.price_list_type==='customer'", $issueForm);
        $this->assertStringNotContainsString("candidate.price_list_type==='global'", $issueForm);
        $this->assertStringContainsString('Người phụ trách', $issueForm);
        $this->assertStringContainsString('Bảng giá xuất', $issueForm);
        $this->assertStringContainsString('name="price_list_id"', $issueForm);
        $this->assertStringContainsString('CUSTOMER · ACTIVE', $issueForm);
        $this->assertStringContainsString("@section('admin_container','full')", $issueForm);
        $this->assertStringNotContainsString('max-w-[1500px]', $issueForm);
        $this->assertStringContainsString('medicineIdsForSelectedPriceList', $issueForm);
        $this->assertStringContainsString('applyPriceListToRow(row,false)', $issueForm);
        $this->assertStringNotContainsString('if(priceListSelect.value) refreshRowsForPriceList();', $issueForm);
        $this->assertStringContainsString('issue-stock-warning', $issueForm);
        $this->assertStringContainsString('Vượt tồn', $issueForm);
        $this->assertStringContainsString('tồn sau xuất', $issueForm);
        $this->assertStringContainsString("classList.toggle('border-rose-500',isNegative)", $issueForm);
        $this->assertStringContainsString('min-h-11 items-center justify-end', $issueForm);
        $this->assertStringContainsString("'price_list_id'=>'required|integer|exists:pharma_price_lists,id'", $controller);
        $this->assertStringContainsString("where('type',PriceList::TYPE_CUSTOMER)->activeAt", $controller);
        $this->assertStringContainsString("'price_list_id'=>\$data['price_list_id']", $controller);
        $this->assertStringContainsString("where('pharma_price_lists.type',PriceList::TYPE_CUSTOMER)", $controller);
        $this->assertStringNotContainsString("whereIn('pharma_price_lists.type',[PriceList::TYPE_CUSTOMER,PriceList::TYPE_GLOBAL])", $controller);
        $priceListMigration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_23_153000_add_price_list_to_pharma_inventory_issues.php'));
        $this->assertStringContainsString("foreignId('price_list_id')->nullable()", $priceListMigration);
        $this->assertStringContainsString("constrained('pharma_price_lists')->nullOnDelete()", $priceListMigration);
        $this->assertStringContainsString('public function revertIssue', $service);
        $this->assertStringContainsString("'type'=>'issue_reversal'", $service);
        $this->assertStringContainsString('Tổng giá trị', $documents);
        $this->assertStringNotContainsString('Tổng SL', $documents);
        $this->assertStringContainsString('Export Excel', $documents);
        $this->assertStringContainsString("@section('admin_container','full')", $documents);
        $this->assertStringContainsString("max-w-[1580px]", $documents);
        $this->assertStringContainsString("min-w-[1120px]", $documents);
        $this->assertStringContainsString('Khách hàng / Nơi nhận', $documents);
        $this->assertStringContainsString('Tải PDF', $documents);
        $this->assertStringContainsString('In trực tiếp', $documents);
        $this->assertStringContainsString('aria-label="Thao tác khác"', $documents);
        $this->assertStringContainsString('min-h-[calc(100vh-7.5rem)]', $documents);
        $this->assertStringContainsString('flex min-h-0 flex-1 flex-col', $documents);
        $this->assertStringContainsString('min-h-[420px] flex-1 overflow-auto', $documents);
        $this->assertStringContainsString('sticky top-0 z-10', $documents);
        $this->assertStringContainsString('absolute right-0 z-30', $documents);
        $this->assertStringContainsString('truncate font-semibold text-slate-800', $documents);
        $this->assertStringContainsString('Thành tiền', $issueShow);
        $this->assertStringContainsString('Tổng giá trị', $issueShow);
        $this->assertStringContainsString('<x-select-search id="issue-edit-recipient"', $issueEdit);
        $this->assertStringContainsString("placeholder:'Tìm mã hoặc tên thuốc...'", $issueForm);
        $this->assertStringContainsString('Chọn lô còn tồn', $issueForm);
        $this->assertStringContainsString('Xác nhận ghi sổ', $documents);
        $this->assertStringContainsString('Ghi sổ · Không đủ tồn', $documents);
        $this->assertStringContainsString('data-document-actions', $documents);
        $this->assertStringContainsString("other.removeAttribute('open')", $documents);
        $this->assertStringContainsString("!menu.contains(event.target)", $documents);
        $this->assertStringContainsString('Không đủ tồn kho để ghi sổ', $documents);
        $this->assertStringContainsString('$doc->can_post_stock', $documents);
        $this->assertStringContainsString('$issue->can_post_stock=', $controller);
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
        $this->assertStringNotContainsString('Import / Export Excel', $index);
        $this->assertStringNotContainsString('Phiếu nhập gần đây', $index);
        $this->assertStringNotContainsString('Phiếu xuất gần đây', $index);
        $this->assertStringContainsString("route('admin.pharma.inventory.movements.index')", $index);
    }

    public function test_issue_draft_editor_and_delivery_document_contracts(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $edit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));
        $show=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));

        $this->assertStringContainsString("['items.medicine','priceList.manager']", $controller);
        $this->assertStringContainsString("'items'=>'required|array|min:1'", $controller);
        $this->assertStringContainsString("\$locked->items()->delete()", $controller);
        $this->assertStringContainsString("\$locked->items()->createMany(\$items)", $controller);
        $this->assertStringContainsString('Lưu phiếu nháp', $edit);
        $this->assertStringContainsString('Lưu & xem phiếu', $edit);
        $this->assertStringContainsString('Tóm tắt phiếu', $edit);
        $this->assertStringContainsString('Tổng số lượng', $edit);
        $this->assertStringContainsString('Tổng giá trị', $edit);
        $this->assertStringContainsString('refreshSummary()', $edit);
        $this->assertStringContainsString('⚠ Tồn sau xuất:', $edit);
        $this->assertStringContainsString("\$request->input('after_save')==='view'", $controller);
        $this->assertStringContainsString('name="price_list_id"', $edit);
        $this->assertStringContainsString('Hàng hóa xuất', $edit);
        $this->assertStringContainsString('+ Thêm sản phẩm', $edit);
        $this->assertStringContainsString("@section('admin_container','full')", $show);
        $this->assertStringContainsString('Đơn giá xuất', $show);
        $this->assertStringContainsString('Bảng giá áp dụng', $show);
        $this->assertStringContainsString('$settings->issuer_label', $show);
        $this->assertStringContainsString('$settings->deliverer_label', $show);
        $this->assertStringContainsString('$settings->receiver_label', $show);
        $this->assertStringNotContainsString('Giá vốn', $show);
    }


    public function test_issue_document_pdf_and_print_contracts(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $show=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $pdf=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-pdf.blade.php'));
        $print=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-print.blade.php'));

        $this->assertStringContainsString("name('issues.pdf')", $routes);
        $this->assertStringContainsString("name('issues.print')", $routes);
        $this->assertStringContainsString('function issuePdf', $controller);
        $this->assertStringContainsString("Pdf::loadView('Pharma::pages.inventory.issue-pdf'", $controller);
        $this->assertStringContainsString("setPaper('a4','portrait')", $controller);
        $this->assertStringContainsString('function issuePrint', $controller);
        $this->assertStringContainsString('↓ Tải PDF', $show);
        $this->assertStringContainsString('▣ In trực tiếp', $show);
        $this->assertStringContainsString('Thông tin chứng từ', $show);
        $this->assertStringContainsString('Chi tiết hàng xuất', $show);
        $this->assertStringContainsString('Tóm tắt phiếu', $show);
        $this->assertStringContainsString('PHIẾU XUẤT KHO', $pdf);
        $this->assertStringContainsString('@page{margin:16mm 12mm}', $pdf);
        $this->assertStringContainsString('$settings->deliverer_label', $pdf);
        $this->assertStringContainsString('$settings->receiver_label', $pdf);
        $this->assertStringNotContainsString('Tổng số lượng:', $pdf);
        $this->assertStringNotContainsString('$totalQuantity', $pdf);
        $this->assertStringNotContainsString('$totalQuantity', $print);
        $this->assertStringContainsString('window.print()', $print);
        $this->assertStringContainsString('@media print', $print);
    }


    public function test_issue_document_settings_contracts(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $settingsView=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-settings.blade.php'));
        $model=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssueDocumentSetting.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_23_164500_create_pharma_inventory_issue_document_settings_table.php'));
        $signatureMigration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_091500_add_signature_options_to_pharma_inventory_issue_document_settings.php'));
        $pdf=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-pdf.blade.php'));
        $print=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-print.blade.php'));
        $show=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));

        $this->assertStringContainsString("name('issues.settings')", $routes);
        $this->assertStringContainsString("name('issues.settings.update')", $routes);
        $this->assertStringContainsString("middleware('can:edit_pharma')", $routes);
        $this->assertStringContainsString('⚙ Cấu hình phiếu xuất', $documents);
        $this->assertStringContainsString('function issueDocumentSettings', $controller);
        $this->assertStringContainsString('function updateIssueDocumentSettings', $controller);
        $this->assertStringContainsString('InventoryIssueDocumentSetting::current()', $controller);
        $this->assertStringContainsString("pharma_inventory_issue_document_settings", $migration);
        $this->assertStringContainsString('organization_name', $settingsView);
        $this->assertStringContainsString('warehouse_name', $settingsView);
        $this->assertStringContainsString('show_unit_price', $settingsView);
        $this->assertStringContainsString('show_total_value', $settingsView);
        $this->assertStringContainsString('issuer_label', $model);
        $this->assertStringContainsString('keeper_label', $model);
        $this->assertStringContainsString('show_keeper_signature', $model);
        $this->assertStringContainsString("string('keeper_label')->default('Thủ kho')", $signatureMigration);
        $this->assertStringContainsString("boolean('show_issuer_signature')->default(true)", $signatureMigration);
        $this->assertStringContainsString("boolean('show_keeper_signature')->default(true)", $signatureMigration);
        $this->assertStringContainsString('show_issuer_signature', $settingsView);
        $this->assertStringContainsString('show_deliverer_signature', $settingsView);
        $this->assertStringContainsString('show_receiver_signature', $settingsView);
        $this->assertStringContainsString('show_keeper_signature', $settingsView);
        $this->assertStringContainsString("'keeper_label'=>'required|string|max:120'", $controller);
        $this->assertStringContainsString("'show_keeper_signature'", $controller);
        $this->assertStringContainsString("class=\"label\">Bảng giá áp dụng:", $pdf);
        $this->assertStringContainsString('$settings->keeper_label', $pdf);
        $this->assertStringContainsString('$settings->keeper_label', $print);
        $this->assertStringContainsString('$settings->keeper_label', $show);
        $this->assertStringContainsString("number_format((float)\$item->quantity,0,',','.')", $pdf);
        $this->assertStringContainsString("number_format((float)\$item->quantity,0,',','.')", $print);
        $this->assertStringContainsString("number_format((float)\$item->quantity,0,',','.')", $show);
        $this->assertStringNotContainsString("number_format((float)\$item->quantity,3,',','.')", $pdf);
        $this->assertStringContainsString("show_notes && filled(\$issue->notes)", $pdf);
        $this->assertStringContainsString("show_notes && filled(\$issue->notes)", $print);
        $this->assertStringContainsString("show_notes && filled(\$issue->notes)", $show);
        $this->assertStringNotContainsString('Không có ghi chú.', $pdf);
        $this->assertStringContainsString("['show'=>\$settings->show_receiver_signature,'label'=>\$settings->receiver_label,'show_date'=>false]", $pdf);
        $this->assertStringContainsString("['show'=>\$settings->show_keeper_signature,'label'=>\$settings->keeper_label,'show_date'=>true]", $pdf);
        $this->assertStringContainsString('Ngày ..... tháng ..... năm .....', $pdf);
        $this->assertStringContainsString('Ngày ..... tháng ..... năm .....', $print);
        $this->assertStringContainsString('Ngày ..... tháng ..... năm .....', $show);

        foreach (['issue-settings.blade.php','issue-show.blade.php','issue-pdf.blade.php','issue-print.blade.php'] as $file) {
            $candidate=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/'.$file));
            try {
                token_get_all(Blade::compileString($candidate), TOKEN_PARSE);
            } catch (\ParseError $error) {
                $this->fail($file.' failed Blade compilation: '.$error->getMessage());
            }
        }
        $this->addToAssertionCount(4);
    }

    public function test_bid_sale_issue_workspace_contracts(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $workspace=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/bid-sale-create.blade.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_111500_add_bid_sale_source_to_pharma_inventory_issues.php'));

        $this->assertStringContainsString("issues/bid-sales/create", $routes);
        $this->assertStringContainsString("issues/bid-sales/allocations", $routes);
        $this->assertStringContainsString("issues/bid-sales", $routes);
        $this->assertStringContainsString('function createBidSaleIssue', $controller);
        $this->assertStringContainsString('function bidSaleAllocations', $controller);
        $this->assertStringContainsString('function storeBidSaleIssue', $controller);
        $this->assertStringContainsString("where('i.issue_source','bid')", $controller);
        $this->assertStringContainsString('drug_bid_award_allocation_id', $controller);
        $this->assertStringContainsString('winning_price', $controller);
        $this->assertStringContainsString('allocated_quantity', $controller);
        $this->assertStringContainsString('+ Xuất bán hàng thầu', $documents);
        $this->assertStringContainsString('Hàng thầu', $documents);
        $this->assertStringContainsString('SL phân bổ', $workspace);
        $this->assertStringContainsString('Đã xuất', $workspace);
        $this->assertStringContainsString('Còn lại', $workspace);
        $this->assertStringContainsString('Đơn giá trúng thầu', $workspace);
        $this->assertStringContainsString('<x-select-search id="investor"', $workspace);
        $this->assertStringContainsString('<x-select-search id="partner"', $workspace);
        $this->assertStringContainsString('<x-select-search id="product-search"', $workspace);
        $this->assertStringContainsString('Tìm theo mã thuốc, tên thuốc, hoạt chất', $workspace);
        $this->assertStringContainsString('Hiệu lực', $workspace);
        $this->assertStringContainsString('days_remaining', $controller);
        $this->assertStringContainsString('Vui lòng nhập số lượng xuất cho ít nhất một sản phẩm.', $controller);
        $this->assertStringContainsString("filter(fn(\$quantity)=>(float)\$quantity>0)", $controller);
        $this->assertStringContainsString("'bid_partner_id'=>\$partner->id", $controller);
        $issueShow=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $issuePdf=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-pdf.blade.php'));
        $issuePrint=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-print.blade.php'));
        foreach([$issueShow,$issuePdf,$issuePrint] as $documentView){
            $this->assertStringContainsString("expiry_date?->format('d/m/Y')", $documentView);
        }
        $this->assertStringContainsString('Chưa chọn lô', $issueShow);
        $this->assertStringNotContainsString("'recipient_partner_id'=>\$partner->id", $controller);
        $this->assertStringNotContainsString('name="allocation_ids[]"', $workspace);
        $this->assertStringContainsString('Tạo phiếu nháp', $workspace);
        $this->assertStringContainsString('chưa trừ tồn kho', $workspace);
        $this->assertStringContainsString('Giá trị dự kiến', $workspace);
        $this->assertStringContainsString('Tối đa', $workspace);
        $this->assertStringNotContainsString('Lô tồn FEFO', $workspace);
        $this->assertStringNotContainsString('balance_ids[', $workspace);
        $this->assertStringContainsString('issues/{issue}/bid-sale-batches', $routes);
        $this->assertStringContainsString('function bidSaleBatches', $controller);
        $this->assertStringContainsString('function postBidSaleIssue', $controller);
        $this->assertStringContainsString('function editBidSaleIssue', $controller);
        $this->assertStringContainsString('function updateBidSaleIssue', $controller);
        $this->assertStringContainsString('issues/{issue}/bid-sale-edit', $routes);
        $this->assertStringContainsString('Sửa đơn hàng thầu', $documents);
        $this->assertStringContainsString("\$doc->status === 'draft' && (\$doc->issue_source ?? 'normal') !== 'bid'", $documents);
        $this->assertStringContainsString('!($type === \'issue\' && ($doc->issue_source ?? \'normal\') === \'bid\')', $documents);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::query()', $controller);
        $this->assertStringNotContainsString("with(['partner','award.medicine','managementAssignments.user'])", $controller);
        $bidEdit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/bid-sale-edit.blade.php'));
        $this->assertStringContainsString('Người phụ trách', $bidEdit);
        $this->assertStringContainsString('Tồn khả dụng', $bidEdit);
        $this->assertStringContainsString('Đơn giá trúng thầu', $bidEdit);
        $this->assertStringContainsString('SL duyệt', $bidEdit);
        $this->assertStringContainsString('SL lấy từ lô', $bidEdit);
        $this->assertStringContainsString('Duyệt & ghi sổ', $bidEdit);
        $this->assertStringContainsString('FEFO là gợi ý ưu tiên', $bidEdit);
        $this->assertStringContainsString('+ Thêm sản phẩm trúng thầu', $bidEdit);
        $this->assertStringContainsString('Sản phẩm trong phiếu', $bidEdit);
        $this->assertStringContainsString('bid-product-count', $bidEdit);
        $this->assertStringContainsString('bid-pending-products', $bidEdit);
        $this->assertStringContainsString('Mới thêm', $bidEdit);
        $this->assertStringContainsString('Chờ lưu nháp', $bidEdit);
        $this->assertStringContainsString("panel.classList.add('hidden')", $bidEdit);
        $this->assertStringContainsString('id="bid-post-button"', $bidEdit);
        $this->assertStringContainsString("const hasPending=pending ? pending.children.length>0 : false", $bidEdit);
        $this->assertStringContainsString("postButton.disabled=hasPending || !hasPostableStock || unresolved", $bidEdit);
        $this->assertStringContainsString('Hãy lưu phiếu nháp để hệ thống tải tồn kho/lô thực tế trước khi duyệt', $bidEdit);
        $this->assertStringContainsString("if(\$request->filled('add_allocations') || \$request->filled('add_quantities'))", $controller);
        $this->assertStringContainsString('Có sản phẩm mới chưa được lưu. Hãy lưu phiếu nháp trước khi Duyệt & ghi sổ.', $controller);
        $deferredModel=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssueDeferredSupply.php'));
        $deferredMigration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_120500_create_pharma_inventory_issue_deferred_supplies_table.php'));
        $issueModel=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssue.php'));
        $issueShow=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $this->assertStringContainsString('pharma_inventory_issue_deferred_supplies', $deferredMigration);
        $this->assertStringContainsString("decimal('quantity',15,3)", $deferredMigration);
        $this->assertStringContainsString("expected_supply_date", $deferredMigration);
        $this->assertStringContainsString('class InventoryIssueDeferredSupply', $deferredModel);
        $this->assertStringContainsString('deferredSupplies()', $issueModel);
        $this->assertStringContainsString('Ghi nhận chờ cung cấp', $bidEdit);
        $this->assertStringContainsString('Lưu ghi chú chờ cấp', $bidEdit);
        $this->assertStringContainsString('✓ Chờ lưu phiếu nháp', $bidEdit);
        $this->assertStringContainsString("'deferred'=>'nullable|array'", $controller);
        $this->assertStringContainsString('InventoryIssueDeferredSupply::updateOrCreate', $controller);
        $this->assertStringContainsString("\$savedDeferred=\$issue->deferredSupplies", $controller);
        $this->assertStringContainsString("\$savedSupply=\$savedDeferred->get(\$row['allocation_id'])", $bidEdit);
        $this->assertStringContainsString("document.querySelectorAll('[data-defer-toggle]').forEach", $bidEdit);
        $this->assertStringNotContainsString("@if(\$addableAllocations->isNotEmpty())\n<script>\ndocument.addEventListener('DOMContentLoaded'", $bidEdit);
        $this->assertStringContainsString("const hasPending=pending ? pending.children.length>0 : false", $bidEdit);
        $this->assertStringContainsString('Hiện kho đang hết hàng. Đơn hàng dự kiến cung cấp lại.', $bidEdit);
        $this->assertStringContainsString('data-defer-enabled', $bidEdit);
        $this->assertStringContainsString("postButton.disabled=hasPending || !hasPostableStock || unresolved", $bidEdit);
        $this->assertStringContainsString("'issue_id'=>\$issue->id,'drug_bid_award_allocation_id'=>\$item->drug_bid_award_allocation_id", $controller);
        $this->assertStringContainsString('Chưa có hàng thực xuất. Phiếu chỉ được ghi sổ', $controller);
        $this->assertStringContainsString('Nhật ký chờ cung cấp', $issueShow);
        $this->assertStringContainsString('Các số lượng này chưa xuất kho và không tạo bút toán trừ tồn.', $issueShow);
        $dedupeMigration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_122000_deduplicate_issue_deferred_supplies.php'));
        $this->assertStringContainsString('ph_inv_issue_deferred_unique', $dedupeMigration);
        $this->assertStringContainsString("havingRaw('COUNT(*) > 1')", $dedupeMigration);
        $this->assertStringContainsString('Ngày lên đơn', $bidEdit);
        $this->assertStringContainsString('type="hidden" name="issue_date"', $bidEdit);
        $this->assertStringNotContainsString('type="date" name="issue_date"', $bidEdit);
        $this->assertStringContainsString('Ngày ghi sổ', $issueShow);
        $this->assertStringContainsString("\$issue->posted_at->format('d/m/Y H:i')", $issueShow);
        $this->assertStringContainsString("\$locked->update(['notes'=>\$data['notes']??null])", $controller);
        $this->assertStringContainsString("\$issue->update(['notes'=>\$data['notes']??null])", $controller);
        $this->assertStringContainsString('Tìm sản phẩm trúng thầu', $bidEdit);
        $this->assertStringContainsString("name='add_allocations[]'", str_replace('"', "'", $bidEdit));
        $this->assertStringContainsString('add_quantities[', $bidEdit);
        $this->assertStringContainsString("where('partner_id',\$issue->bid_partner_id)", $controller);
        $this->assertStringContainsString("whereNotIn('id',\$allocationIds)", $controller);
        $this->assertStringContainsString("'add_allocations'=>'nullable|array'", $controller);
        $this->assertStringContainsString('Sản phẩm trúng thầu đã có trong phiếu.', $controller);
        $this->assertStringContainsString('Có sản phẩm không còn thuộc phân bổ hợp lệ của Chủ đầu tư/Bệnh viện này.', $controller);
        $this->assertStringContainsString('$hasPostableStock=$rows->contains', $bidEdit);
        $this->assertStringContainsString('$stockReady=$hasPostableStock && !$hasUnresolvedShortage', $bidEdit);
        $this->assertStringContainsString('@disabled(!$stockReady)', $bidEdit);
        $this->assertStringContainsString('Chưa thể duyệt vì có mặt hàng chưa có lô tồn khả dụng', $bidEdit);
        $this->assertStringContainsString('Xóa khỏi đơn', $bidEdit);
        $this->assertStringContainsString('Lưu phiếu nháp', $bidEdit);
        $this->assertStringContainsString('@if($canApprove)', $bidEdit);
        $this->assertStringContainsString("can('approve_pharma_inventory_issue')", $controller);
        $this->assertStringContainsString("can:approve_pharma_inventory_issue", $routes);
        $moduleConfig=file_get_contents(base_path('Modules/Pharma/config/module.php'));
        $this->assertStringContainsString("'approve_pharma_inventory_issue'", $moduleConfig);
        $this->assertStringContainsString("remove_items", $controller);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.index')", $bidEdit);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.bid-sales.post',\$issue)", $bidEdit);
        $this->assertStringContainsString("'quantities'=>'required|array'", $controller);
        $this->assertStringContainsString('Tổng số lượng chia lô', $controller);
        $this->assertStringContainsString('không đủ tồn để xuất', $controller);
        $this->assertStringNotContainsString('Bảng giá xuất', $bidEdit);
        $this->assertStringContainsString("batch_number'=>null", $controller);
        $this->assertStringContainsString("issue_source ?? 'normal')==='bid'", $controller);
        $this->assertStringNotContainsString("return redirect()->route('admin.pharma.inventory.issues.show',\$issue)", substr($controller, strpos($controller, 'public function showIssue'), strpos($controller, 'public function issuePdf') - strpos($controller, 'public function showIssue')));
        $issueEdit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));
        $this->assertStringContainsString("'expiry_date'=>\$item->expiry_date?->format('Y-m-d')", $issueEdit);
        $batchWorkspace=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/bid-sale-batches.blade.php'));
        $this->assertStringContainsString('Chọn lô & ghi sổ hàng thầu', $batchWorkspace);
        $this->assertStringContainsString('tồn kho chỉ được trừ sau khi xác nhận ghi sổ', $batchWorkspace);
        $this->assertStringContainsString('FEFO', $batchWorkspace);
        $this->assertStringContainsString('issue_source', $migration);
        $this->assertStringContainsString('drug_bid_award_allocation_id', $migration);

        foreach(['bid-sale-create.blade.php','bid-sale-edit.blade.php','bid-sale-batches.blade.php'] as $file){
            $candidate=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/'.$file));
            try { token_get_all(Blade::compileString($candidate), TOKEN_PARSE); }
            catch (\ParseError $error) { $this->fail($file.' failed Blade compilation: '.$error->getMessage()); }
        }
        $this->addToAssertionCount(3);
    }


}