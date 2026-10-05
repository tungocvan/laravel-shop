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
        $this->assertStringContainsString("$lotAllocations=\$postedItems->groupBy", $service);
        $this->assertStringContainsString('$balance->quantity_on_hand < $required', $service);
        $this->assertStringContainsString('Tồn đầu kỳ', $view);
        $this->assertStringContainsString('Sắp hết hạn', $view);
    }

    public function test_inventory_index_blade_compiles_to_valid_php(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/index.blade.php'));
        $compiled = Blade::compileString($view);

        $this->assertNotEmpty($compiled);
        token_get_all($compiled, TOKEN_PARSE);
        foreach (['issue-form.blade.php','documents.blade.php','receipt-show.blade.php','receipt-edit.blade.php','issue-show.blade.php','issue-edit.blade.php','receipt-settings.blade.php','receipt-pdf.blade.php','receipt-print.blade.php'] as $file) {
            $candidate=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/'.$file));
            try {
                token_get_all(Blade::compileString($candidate), TOKEN_PARSE);
            } catch (\ParseError $e) {
                $this->fail($file.': '.$e->getMessage());
            }
        }
        $this->addToAssertionCount(10);
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
        $this->assertStringContainsString('BinaryFileResponse', $controller);
        $this->assertStringContainsString('new Spreadsheet()', file_get_contents(base_path('Modules/Pharma/Services/CommissionExcelExportService.php')));
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
        $this->assertStringContainsString('this.$wire.set(config.model, value);', $searchSelect);
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
        $this->assertStringContainsString("route('admin.pharma.inventory.receipts.show',\$doc)", $documents);
        $this->assertStringContainsString("data-document-actions", $documents);
        $this->assertStringContainsString("aria-label=\"Thao tác khác\">⋯", $documents);
        $this->assertStringNotContainsString('>Xem</a>', $documents);
        $this->assertStringContainsString("name('receipts.settings')", $routes);
        $this->assertStringContainsString("name('receipts.settings.update')", $routes);
        $this->assertStringContainsString('function receiptDocumentSettings(', $controller);
        $this->assertStringContainsString('function updateReceiptDocumentSettings(', $controller);
        $this->assertStringContainsString('InventoryReceiptDocumentSetting::current()', $controller);
        $receiptSettings=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-settings.blade.php'));
        foreach (['Cấu hình phiếu nhập kho','Giá nhập','Giá HĐ chưa VAT','VAT','Chữ ký: Thủ kho'] as $receiptSettingLabel) {
            $this->assertStringContainsString($receiptSettingLabel,$receiptSettings);
        }
        $this->assertStringContainsString("route('admin.pharma.inventory.receipts.settings')", $documents);
        $this->assertStringContainsString("name('receipts.pdf.invoice')", $routes);
        $this->assertStringContainsString("name('receipts.print.invoice')", $routes);
        $this->assertStringContainsString("middleware('can:view_pharma_inventory_costs')->name('receipts.pdf.cost')", $routes);
        $this->assertStringContainsString("middleware('can:view_pharma_inventory_costs')->name('receipts.print.cost')", $routes);
        $this->assertStringContainsString("name('receipts.pdf.invoice.export')", $routes);
        $this->assertStringContainsString("name('receipts.pdf.cost.export')", $routes);
        $this->assertStringContainsString('function exportReceiptInvoicePdf(', $controller);
        $this->assertStringContainsString('function exportReceiptCostPdf(', $controller);
        $this->assertStringContainsString('function downloadReceiptInvoicePdf(', $controller);
        $this->assertStringContainsString('function printReceiptInvoicePdf(', $controller);
        $this->assertStringContainsString("abort_unless(request()->user()?->can('view_pharma_inventory_costs'),403)", $controller);
        $receiptDocumentService=file_get_contents(base_path('Modules/Pharma/Services/InventoryReceiptDocumentService.php'));
        $this->assertStringContainsString("Storage::disk('local')->put(\$path, \$binary)", $receiptDocumentService);
        $this->assertStringContainsString("Pdf::loadView('Pharma::pages.inventory.receipt-pdf'", $receiptDocumentService);
        $this->assertStringContainsString('sourceHash(', $receiptDocumentService);
        $this->assertStringContainsString('createInvoiceShare(', $receiptDocumentService);
        $this->assertStringContainsString("abort_unless(\$receipt->status === InventoryReceipt::POSTED, 409, 'Chỉ phiếu nhập đã ghi sổ mới được xuất PDF.')", $receiptDocumentService);
        $this->assertStringContainsString('@if($canUseReceiptPdf)', $documents);
        $this->assertStringContainsString("route('admin.pharma.inventory.receipts.approve',\$doc)", $documents);
        $this->assertStringContainsString("document.getElementById('post-receipt-{{ \$doc->id }}').showModal()", $documents);
        $this->assertStringContainsString("route('admin.pharma.inventory.receipts.pdf.invoice',\$doc)", $documents);
        $this->assertStringContainsString("@can('view_pharma_inventory_costs')", $documents);
        $this->assertStringContainsString("route('admin.pharma.inventory.receipts.pdf.cost',\$doc)", $documents);
        $issueDocumentService=file_get_contents(base_path('Modules/Pharma/Services/InventoryIssueDocumentService.php'));
        $this->assertStringContainsString("name('issues.pdf.export')",$routes);
        $this->assertStringContainsString('function exportIssuePdf(',$controller);
        $this->assertStringContainsString('function downloadIssuePdf(',$controller);
        $this->assertStringContainsString('function printIssuePdf(',$controller);
        $this->assertStringContainsString("abort_unless(\$issue->status===InventoryIssue::POSTED,409,'Chỉ phiếu xuất đã ghi sổ mới được xuất PDF.')",$issueDocumentService);
        $this->assertStringContainsString("Storage::disk('local')->put(\$path,\$binary)",$issueDocumentService);
        $this->assertStringContainsString("Pdf::loadView('Pharma::pages.inventory.issue-pdf'",$issueDocumentService);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.pdf.export',\$doc)",$documents);
        $this->assertStringContainsString("@if(\$doc->status === 'posted')",$documents);
        $receiptPdf=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/receipt-pdf.blade.php'));
        foreach (['Nhà cung cấp','Hóa đơn','Số lô','Hạn dùng','Giá nhập','VAT','Giá HĐ chưa VAT','Thành tiền giá vốn','Tiền VAT','Tổng thanh toán'] as $receiptPdfLabel) {
            $this->assertStringContainsString($receiptPdfLabel,$receiptPdf);
        }
        $this->assertStringContainsString("\$isCost=\$profile === 'cost'", $receiptPdf);
        $this->assertStringContainsString("@if(\$isCost)", $receiptPdf);
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
        $this->assertStringContainsString('$canPostStock=false;', $controller);
        $this->assertStringContainsString("->groupBy(fn($item)=>$item->medicine_id.'|'.$item->batch_number", $controller);
        $this->assertStringContainsString(">= (float)$items->sum('quantity')", $controller);
        $this->assertStringContainsString('@if($canPostStock)', $issueShow);
        $this->assertStringContainsString('Phân bổ nhiều lô (FEFO)', $issueEdit);
        $this->assertStringContainsString('function allocateFefo(tr)', $issueEdit);
        $this->assertStringContainsString("sort((a,b)=>String(a.expiry||'9999-12-31').localeCompare", $issueEdit);
        $this->assertStringContainsString('allocations.forEach(allocation=>addRow(allocation))', $issueEdit);
        $this->assertStringContainsString('<x-select-search id="issue-manager-filter"', $documents);
        $this->assertStringContainsString('<x-select-search id="issue-recipient-filter"', $documents);
        $this->assertStringContainsString('Người phụ trách', $documents);
        $this->assertStringContainsString('data-select-page', $documents);
        $this->assertStringContainsString('data-row-select', $documents);
        $this->assertStringContainsString('Xuất Excel đã chọn', $documents);
        $this->assertStringContainsString("request()->only(['q','status','manager_user_id','recipient_name','date_from','date_to'])", $documents);
        $this->assertStringContainsString("\$managerId=\$request->filled('manager_user_id')", $controller);
        $this->assertStringContainsString("filter_assignments.user_id", $controller);
        $this->assertStringContainsString("issueManagerNames(\$issue)", $controller);
        $this->assertStringContainsString("resolved_manager_names", $documents);
        $this->assertStringContainsString('data-auto-submit-filter', $documents);
        $this->assertStringContainsString("filterForm?.requestSubmit()", $documents);
        $this->assertStringContainsString('Đặt lại', $documents);
        $this->assertStringContainsString('id="issue-keyword-filter"', $documents);
        $this->assertStringContainsString('data-clear-keyword', $documents);
        $this->assertStringContainsString('name="date_from"', $documents);
        $this->assertStringContainsString('name="date_to"', $documents);
        $this->assertStringContainsString('aria-label="Từ ngày"', $documents);
        $this->assertStringContainsString('aria-label="Đến ngày"', $documents);
        $this->assertStringContainsString('class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10', $documents);
        $this->assertStringNotContainsString('>Từ ngày<input type="date"', $documents);
        $this->assertStringNotContainsString('>Đến ngày<input type="date"', $documents);
        $this->assertStringContainsString("keyword.value='';filterForm?.requestSubmit()", $documents);
        $this->assertStringContainsString("now()->startOfMonth()->toDateString()", $controller);
        $this->assertStringContainsString("now()->toDateString()", $controller);
        $this->assertStringContainsString("whereBetween('issue_date',[\$dateFrom,\$dateTo])", $controller);
        $this->assertStringContainsString("\$selectedManagerId=\$issue->manager_user_id ?:", $issueEdit);
        $this->assertStringContainsString("name=\"price_list_id\" value=\"{{ \$selectedPriceList?->id }}\"", $issueEdit);
        $this->assertStringContainsString('Đã khóa', $issueEdit);
        $this->assertStringContainsString('Bảng giá của phiếu đã lập không thể thay đổi.', $issueEdit);
        $this->assertStringContainsString("priceListManagers=app(UserOrderAuthoringService::class)->orderManagers()", $controller);
        $this->assertStringContainsString("@foreach(\$priceListManagers as \$manager)", $issueEdit);
        $this->assertStringContainsString('Chỉ User được phân công cho bảng giá này mới được phép phụ trách phiếu.', $issueEdit);
        $this->assertStringNotContainsString('refreshEditPriceLists', $issueEdit);
        $this->assertStringContainsString("(int)\$data['price_list_id'] !== (int)\$issue->price_list_id", $controller);
        $this->assertStringContainsString('Bảng giá áp dụng của phiếu đã lập không được phép thay đổi.', $controller);
        $this->assertStringContainsString("? (\$priceList->globalUsers->isEmpty() || \$priceList->globalUsers->contains", $controller);
        $this->assertStringContainsString("Bảng giá áp dụng không còn hoạt động hoặc không còn hiệu lực tại ngày xuất.", $controller);
        $this->assertStringContainsString("Khách hàng không còn hoạt động. Vui lòng chọn lại khách hàng / nơi nhận.", $controller);
        $this->assertStringContainsString("Lô tồn kho đã chọn không còn khả dụng. Vui lòng chọn lại lô.", $controller);
        $this->assertStringContainsString("when(\$request->filled('recipient_name')", $controller);
        $this->assertStringContainsString("collect(\$request->input('ids',[]))", $controller);
        $this->assertStringContainsString("'Nguoi phu trach'=>\$issue->manager?->name", $controller);
        $this->assertStringContainsString("'Nguon'=>(\$issue->issue_source ?? 'normal')==='bid'?'Hang thau':'Bang gia'", $controller);
        $this->assertStringContainsString("'items.*.unit_price'=>'required|numeric|min:0'", $controller);
        $this->assertStringContainsString("'unit_price'=>(float)\$item['unit_price']", $controller);
        $this->assertStringContainsString('issueSalePriceCandidates', $controller);
        $this->assertStringContainsString("PriceList::STATUS_ACTIVE", $controller);
        $this->assertStringContainsString("company_sale_price", $controller);
        $this->assertStringContainsString('Đơn giá xuất', $issueForm);
        $this->assertStringContainsString('Giá bảng:', $issueForm);
        $this->assertStringNotContainsString('Giá bán CT ·', $issueForm);
        $this->assertStringContainsString("data-field=\"unit_price\"", $issueForm);
        $this->assertStringContainsString("value=\"0\"", $issueForm);
        $this->assertStringContainsString('có thể nhập tay', $issueForm);
        $this->assertStringContainsString("['global','customer'].includes(candidate.price_list_type)", $issueForm);
        $this->assertStringContainsString("list.type==='global'", $issueForm);
        $this->assertStringContainsString("list.type==='customer'", $issueForm);
        $this->assertStringContainsString('Người phụ trách', $issueForm);
        $this->assertStringContainsString('Bảng giá áp dụng', $issueForm);
        $this->assertStringContainsString('name="price_list_id"', $issueForm);
        $this->assertStringContainsString('bảng giá phù hợp', $issueForm);
        $this->assertStringNotContainsString('CUSTOMER · ACTIVE', $issueForm);
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
        $this->assertStringContainsString("whereIn('type',[PriceList::TYPE_GLOBAL,PriceList::TYPE_CUSTOMER])->activeAt", $controller);
        $this->assertStringContainsString("'price_list_id'=>\$data['price_list_id']", $controller);
        $this->assertStringContainsString("whereIn('pharma_price_lists.type',[PriceList::TYPE_GLOBAL,PriceList::TYPE_CUSTOMER])", $controller);
        $this->assertStringContainsString('Bảng giá không được phân cho Người phụ trách đã chọn.', $controller);
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
        $this->assertStringContainsString("min-w-[1320px]", $documents);
        $this->assertStringContainsString('Khách hàng / Nơi nhận', $documents);
        $this->assertStringContainsString('Tải PDF', $documents);
        $this->assertStringContainsString('In PDF', $documents);
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
        $this->assertStringContainsString("'status'=>\$receipt->approved_at ? InventoryReceipt::APPROVED : InventoryReceipt::DRAFT,'posted_by'=>null,'posted_at'=>null", $service);
        $this->assertStringContainsString('$balance->delete()', $service);
        $this->assertStringContainsString("@can('delete_pharma')", $documents);
        $this->assertStringContainsString("in_array(\$doc->status, ['draft','rejected'], true)", $documents);
        $this->assertStringContainsString("'Xóa phiếu xuất đã từ chối?'", $documents);
        $this->assertStringContainsString("[InventoryIssue::DRAFT,InventoryIssue::REJECTED]", $controller);
        $this->assertStringContainsString('Chỉ phiếu xuất nháp hoặc đã từ chối mới được xóa.', $controller);
        $this->assertStringContainsString('Đã xóa phiếu xuất chưa ghi sổ.', $controller);
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

        $issueDocumentService=file_get_contents(base_path('Modules/Pharma/Services/InventoryIssueDocumentService.php'));
        $this->assertStringContainsString("loadMissing(['items.medicine','manager:id,name','priceList.manager'])", $issueDocumentService);
        $this->assertStringContainsString("'items'=>'required|array|min:1'", $controller);
        $this->assertStringContainsString("\$locked->items()->delete()", $controller);
        $this->assertStringContainsString("foreach(\$items as \$item)", $controller);
        $this->assertStringContainsString("\$created=\$locked->items()->create(\$item)", $controller);
        $this->assertStringContainsString("\$locked->deferredSupplies()->create(", $controller);
        $this->assertStringContainsString('Lưu phiếu nháp', $edit);
        $this->assertStringContainsString("'Lưu phiếu nháp' : 'Lưu thay đổi'", $edit);
        $this->assertStringContainsString("[InventoryIssue::DRAFT,InventoryIssue::PENDING_APPROVAL,InventoryIssue::APPROVED]", $controller);
        $this->assertStringContainsString('Phiếu không còn ở trạng thái cho phép cập nhật xử lý kho.', $controller);
        $this->assertStringContainsString('Lưu & xem phiếu', $edit);
        $this->assertStringContainsString('id="issue-edit-form"', $edit);
        $this->assertStringContainsString('id="issue-save-errors"', $edit);
        $this->assertStringContainsString('type="submit" form="issue-edit-form" name="after_save" value="view"', $edit);
        $this->assertStringContainsString('type="submit" form="issue-edit-form" name="after_save" value="edit"', $edit);
        $this->assertStringContainsString("document.getElementById('issue-edit-form')?.addEventListener('submit'", $edit);
        $this->assertStringContainsString('Tóm tắt phiếu', $edit);
        $this->assertStringContainsString('value="approve"', $edit);
        $this->assertStringContainsString('Duyệt phiếu', $edit);
        $this->assertStringContainsString("status!==InventoryIssue::APPROVED", $controller);
        $this->assertStringContainsString('Phiếu phải được duyệt trước khi ghi sổ.', $controller);
        $this->assertStringNotContainsString('id="summary-lines"', $edit);
        $this->assertStringNotContainsString('id="summary-quantity"', $edit);
        $this->assertStringNotContainsString('id="summary-value"', $edit);
        $this->assertStringContainsString("\$deferredByMedicine=", $show);
        $this->assertStringContainsString("\$deferredValue=", $show);
        $this->assertStringContainsString("\$availableValue=max(0,\$totalValue-\$deferredValue)", $show);
        $this->assertStringContainsString('Tổng giá trị đơn', $show);
        $this->assertStringContainsString('Có thể xuất hiện tại', $show);
        $this->assertStringContainsString('Chờ cung ứng', $show);
        $this->assertStringContainsString('Tổng số lượng', $edit);
        $this->assertStringContainsString('Tổng giá trị', $edit);
        $this->assertStringContainsString('refreshSummary()', $edit);
        $this->assertStringContainsString('⚠ Tồn sau xuất:', $edit);
        $this->assertStringContainsString("\$request->input('after_save')==='view'", $controller);
        $this->assertStringContainsString('name="price_list_id"', $edit);
        $this->assertStringContainsString('Thuốc xuất kho', $edit);
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
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryIssueDocumentService.php'));
        $this->assertStringContainsString('function exportIssuePdf', $controller);
        $this->assertStringContainsString('function downloadIssuePdf', $controller);
        $this->assertStringContainsString('function printIssuePdf', $controller);
        $this->assertStringContainsString("Pdf::loadView('Pharma::pages.inventory.issue-pdf'", $service);
        $this->assertStringContainsString("setPaper('a4','portrait')", $service);
        $this->assertStringContainsString('↓ Tải PDF', $show);
        $this->assertStringContainsString('▣ In PDF', $show);
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

    public function test_issue_draft_requires_customer_and_product_and_issue_index_sums_line_values(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));

        $this->assertStringContainsString("'recipient_partner_id'=>'required|integer|exists:partners,id'", $controller);
        $this->assertStringContainsString("withSum('items as total_value',DB::raw('quantity * unit_price'))", $controller);
        $this->assertStringContainsString('id="issue-save-draft" disabled', $view);
        $this->assertStringContainsString('function updateSaveDraftState()', $view);
        $this->assertStringContainsString('hasRecipient && hasProduct', $view);
        $this->assertStringContainsString('Chọn khách hàng và ít nhất một sản phẩm trước khi lưu nháp.', $view);
        $this->assertStringContainsString('issue-reset-price', $view);
        $this->assertStringContainsString('reset.disabled=!changed', $view);
        $this->assertStringNotContainsString('issue-reset-price hidden', $view);
    }

    public function test_posted_bid_issue_does_not_route_into_draft_editor(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));

        $this->assertStringContainsString("if(\$issue->status===InventoryIssue::DRAFT)", $controller);
        $this->assertStringContainsString('Phiếu hàng thầu đã ghi sổ; không thể chỉnh sửa nội dung đơn.', $controller);
        $this->assertStringContainsString("@if((\$doc->issue_source ?? 'normal') === 'bid')", $documents);
        $this->assertStringContainsString("@if(\$doc->status === 'draft')", $documents);
        $this->assertStringContainsString('Sửa đơn hàng thầu', $documents);
    }

    public function test_bid_issue_uses_dedicated_editor_and_assignment_manager_context(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $show=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $bidCreate=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/bid-sale-create.blade.php'));
        $bidEdit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/bid-sale-edit.blade.php'));

        $this->assertStringContainsString("route('admin.pharma.inventory.issues.bid-sales.edit',\$issue)", $controller);
        $this->assertStringContainsString('Phiếu hàng thầu phải được chỉnh sửa tại workspace Xuất hàng thầu.', $controller);
        $this->assertStringContainsString('private function bidIssueManagerNames', $controller);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::query()', $controller);
        $this->assertStringContainsString("where('partner_id',\$partnerId)", $controller);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.bid-sales.edit',\$issue)", $show);
        $this->assertStringContainsString('Sửa đơn hàng thầu', $show);
        $this->assertStringContainsString('$bidManagerNames', $show);
        $this->assertStringContainsString('Chủ đầu tư / Gói thầu', $show);
        $this->assertStringContainsString('Xuất bán hàng thầu', $bidCreate);
        $this->assertStringContainsString('Sửa & duyệt đơn hàng thầu', $bidEdit);
        $this->assertStringNotContainsString("route('admin.pharma.inventory.issues.update',\$issue)", $bidEdit);
    }

    public function test_issue_persists_selected_manager_and_show_uses_issue_manager(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $model=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssue.php'));
        $show=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-show.blade.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_27_120000_add_manager_user_to_pharma_inventory_issues.php'));
        $this->assertStringContainsString("'manager_user_id'=>\$data['manager_user_id']", $controller);
        $this->assertStringContainsString("'manager:id,name'", $controller);
        $this->assertStringContainsString("belongsTo(\\App\\Models\\User::class,'manager_user_id')", $model);
        $this->assertStringContainsString('$issue->manager?->name', $show);
        $this->assertStringNotContainsString('$issue->priceList?->manager?->name', $show);
        $this->assertStringContainsString("foreignId('manager_user_id')->nullable()", $migration);
    }

    public function test_issue_edit_matches_create_workspace_and_assigned_price_lists(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));

        $this->assertStringContainsString("whereIn('type',[PriceList::TYPE_GLOBAL,PriceList::TYPE_CUSTOMER])", $controller);
        $this->assertStringContainsString("'manager_user_id'=>'required|integer|exists:users,id'", $controller);
        $this->assertStringContainsString('Bảng giá không được phân cho Người phụ trách đã chọn.', $controller);
        $this->assertStringContainsString('<x-select-search id="issue-edit-manager"', $view);
        $this->assertStringContainsString('Ngày xuất', $view);
        $this->assertStringContainsString('Bảng giá áp dụng', $view);
        $this->assertStringContainsString('Khách hàng / nơi nhận', $view);
        $this->assertStringContainsString('Giá bảng:', $view);
        $this->assertStringContainsString('Đặt lại giá gốc', $view);
        $this->assertStringContainsString('parseViNumber', $view);
        $this->assertStringContainsString('formatViNumber', $view);
        $this->assertStringNotContainsString('data-global-users', $view);
        $this->assertStringContainsString('Bảng giá của phiếu đã lập không thể thay đổi.', $view);
        $this->assertStringContainsString('Chỉ User được phân công cho bảng giá này mới được phép phụ trách phiếu.', $view);
        $this->assertStringNotContainsString('max-w-[1580px]', $view);
    }

    public function test_issue_create_and_edit_normalize_localized_numeric_values(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $create=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));
        $edit=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));

        $this->assertSame(2,substr_count($controller,'$this->normalizeIssueNumericInputs($request);'));
        $this->assertStringContainsString('private function parseLocalizedNumber', $controller);
        $this->assertStringContainsString("str_contains(\$raw,',')", $controller);
        $this->assertStringContainsString("preg_match('/\\.\\d{3}$/',\$raw)", $controller);
        $this->assertStringContainsString('parseViNumber', $create);
        $this->assertStringContainsString('parseViNumber', $edit);
        $this->assertStringContainsString("q.value=String(parseViNumber(q.value))", $edit);
        $this->assertStringContainsString("p.value=String(parseViNumber(p.value))", $edit);
        $this->assertStringNotContainsString("Number(tr.querySelector('.qty')?.value||0)", $edit);
        $this->assertStringNotContainsString("Number(tr.querySelector('.price')?.value||0)", $edit);
    }

    public function test_issue_lines_format_numbers_and_can_restore_original_price(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));

        $this->assertStringContainsString('inputmode="decimal"', $view);
        $this->assertStringContainsString('tabular-nums', $view);
        $this->assertStringContainsString('issue-reset-price', $view);
        $this->assertStringContainsString('Đặt lại giá gốc', $view);
        $this->assertStringContainsString('row.dataset.originalPrice', $view);
        $this->assertStringContainsString('Giá bảng:', $view);
        $this->assertStringContainsString('parseViNumber', $view);
        $this->assertStringContainsString('formatViNumber', $view);
        $this->assertStringContainsString('normalizeNumericInput', $view);
        $this->assertStringContainsString("quantity.value=String(parseViNumber(quantity.value))", $view);
        $this->assertStringContainsString("price.value=String(parseViNumber(price.value))", $view);
    }

    public function test_issue_recipient_uses_search_then_customer_card(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));

        $this->assertStringContainsString('md:grid-cols-3', $view);
        $this->assertStringContainsString('Tìm tên khách hàng, bệnh viện, mã số thuế...', $view);
        $this->assertStringContainsString('issue-recipient-picker', $view);
        $this->assertStringContainsString('issue-recipient-card', $view);
        $this->assertStringContainsString('issue-recipient-card-name', $view);
        $this->assertStringContainsString('issue-recipient-change', $view);
        $this->assertStringContainsString('Thay đổi', $view);
        $this->assertStringContainsString('updateRecipientCard', $view);
        $this->assertStringContainsString("'tax_code'=>\$partner->tax_code", $view);
        $this->assertStringNotContainsString('issue-context-summary', $view);
    }

    public function test_issue_create_is_an_order_entry_workspace(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));

        $this->assertStringContainsString('Thiết lập nhanh phiếu xuất', $view);
        $this->assertStringContainsString('Thuốc xuất kho', $view);
        $this->assertStringContainsString('+ Thêm thuốc', $view);
        $this->assertStringContainsString('issue-item-count', $view);
        $this->assertStringContainsString('issue-grand-total', $view);
        $this->assertStringContainsString('issue-footer-quantity', $view);
        $this->assertStringContainsString('sticky bottom-3', $view);
        $this->assertStringContainsString('Bảng giá chung', $view);
        $this->assertStringContainsString('Bảng giá khách hàng', $view);
        $this->assertStringContainsString('bảng giá phù hợp', $view);
        $this->assertStringContainsString('lotSelect.value=String(matching[0].id)', $view);
        $this->assertStringNotContainsString('GLOBAL/CUSTOMER · ACTIVE · còn hiệu lực', $view);
    }

    public function test_normal_issue_uses_assigned_global_or_customer_price_lists_and_guided_header(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-form.blade.php'));
        $editView=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-edit.blade.php'));
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));

        $this->assertStringContainsString("whereIn('type',[PriceList::TYPE_GLOBAL,PriceList::TYPE_CUSTOMER])", $controller);
        $this->assertStringContainsString("'globalUsers:id,name'", $controller);
        $this->assertStringContainsString("app(UserOrderAuthoringService::class)->orderManagers()", $controller);
        $this->assertStringContainsString("? (\$priceList->globalUsers->isEmpty() || \$priceList->globalUsers->contains", $controller);
        $this->assertStringContainsString('Bảng giá không được phân cho Người phụ trách đã chọn.', $controller);
        $this->assertStringContainsString("withSum('items as total_quantity','quantity')", $controller);
        $this->assertStringContainsString("InventoryTransaction::query()->where('source_type',InventoryIssue::class)", $controller);
        $this->assertStringContainsString("\$postedItems=\$issue->items->filter", $controller);
        $this->assertStringContainsString("\$issue->items_count=\$postedItems->count()", $controller);
        $this->assertStringContainsString("\$issue->total_value=(float)\$postedItems->sum", $controller);
        $this->assertStringContainsString("\$issue->shortage_note=\$issue->deferredSupplies->map", $controller);
        $this->assertStringContainsString('Xem ghi chú thiếu hàng', $documents);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.show',\$doc)", $documents);
        $this->assertStringContainsString("shortage-note-{{ \$doc->id }}", $documents);
        $this->assertStringContainsString('Xem ghi chú thiếu hàng', $documents);
        $this->assertStringNotContainsString('>Xem</a>\n                                        <details', $documents);
        $this->assertStringContainsString("\$doc->shortage_note", $documents);
        $this->assertStringContainsString("'Ghi chu thieu hang'=>\$shortage", $controller);
        $this->assertStringContainsString("request->input('after_save')==='post'", $controller);
        $this->assertStringContainsString("postIssue(\$issue->fresh('items')", $controller);
        $this->assertStringContainsString("\$issue->load(['items','deferredSupplies'])", $service);
        $this->assertStringContainsString("\$deferredMedicineIds=\$issue->deferredSupplies->pluck('medicine_id')", $service);
        $this->assertStringContainsString("\$postedItems=\$issue->items->reject", $service);
        $this->assertStringContainsString('toàn bộ mặt hàng đang chờ cung ứng', $service);
        $this->assertStringContainsString("foreach (\$postedItems as \$item)", $service);
        $this->assertStringContainsString("foreach (\$postedItems as \$item) \$this->move", $service);
        $this->assertStringContainsString("snapshotPostedIssue(\$issue->fresh(['items','deferredSupplies'])", $service);
        $this->assertStringContainsString('id="save-post-issue"', $editView);
        $this->assertStringContainsString("->merge(\$issue->items->pluck('medicine'))", $editView);
        $this->assertStringContainsString("'items.*.balance_id'=>'nullable|exists:pharma_inventory_balances,id'", $controller);
        $this->assertStringContainsString("'items.*.supply_note'=>'nullable|string|max:2000'", $controller);
        $this->assertStringContainsString("deferredSupplies()->whereNull('drug_bid_award_allocation_id')->delete()", $controller);
        $this->assertStringContainsString("InventoryIssueDeferredSupply::PENDING", $controller);
        $this->assertStringContainsString('id="supply-note-modal"', $editView);
        $this->assertStringContainsString('+ Ghi chú cung ứng', $editView);
        $this->assertStringContainsString('✓ Đã có ghi chú', $editView);
        $this->assertStringContainsString("const totalStock=balances.filter", $editView);
        $this->assertStringContainsString("const hasShortage=q>0&&shortage>0.00005", $editView);
        $this->assertStringContainsString("supplyModal?.showModal()", $editView);
        $this->assertStringContainsString("items->map(function (\$item) use (\$issue)", $editView);
        $this->assertStringContainsString('expected_supply_date', $editView);
        $this->assertStringContainsString('supply_note', $editView);
        $this->assertStringContainsString('Mặt hàng chưa đủ tồn phải có Ghi chú cung ứng.', $controller);
        $this->assertStringContainsString("->sum('quantity_on_hand')", $controller);
        $this->assertStringContainsString('$hasShortage=$availableQuantity+0.00005', $controller);
        $this->assertStringContainsString("->filter()", $editView);
        $this->assertStringContainsString("->unique('id')", $editView);
        $this->assertStringContainsString("priceListManagers=app(UserOrderAuthoringService::class)->orderManagers()", $controller);
        $this->assertStringContainsString("priceListManagers->contains('id',\$issue->manager->id)", $controller);
        $this->assertStringContainsString("@selected((string)old('manager_user_id',\$selectedManagerId)===(string)\$manager->id)", $editView);
        $this->assertStringContainsString('refreshPostState()', $editView);
        $this->assertStringContainsString("const postable=active.filter(tr=>!(tr.querySelector('.supply-note-value')?.value.trim()))", $editView);
        $this->assertStringContainsString("postable.length>0&&postable.every", $editView);
        $this->assertStringContainsString('Ghi sổ các mặt hàng đủ hàng; mặt hàng có ghi chú cung ứng sẽ không xuất kho', $editView);
        $this->assertStringContainsString('id="aside-post-value"', $editView);
        $this->assertStringContainsString('Ghi sổ lần này', $editView);
        $this->assertStringContainsString('Không xuất · chờ cung ứng', $editView);
        $this->assertStringContainsString('$doc->total_quantity', $documents);
        $this->assertStringContainsString("compact('warehouse','availableBalances','partners','customerPriceLists','priceListManagers','issueSalePrices')", $controller);
        $this->assertStringContainsString("'manager_user_id'=>'required|integer|exists:users,id'", $controller);
        $this->assertStringContainsString('Bảng giá không được phân cho Người phụ trách đã chọn.', $controller);
        $this->assertStringContainsString("whereIn('pharma_price_lists.type',[PriceList::TYPE_GLOBAL,PriceList::TYPE_CUSTOMER])", $controller);
        $this->assertStringContainsString('Thiết lập nhanh phiếu xuất', $view);
        $this->assertStringContainsString('<x-select-search id="issue-price-manager"', $view);
        $this->assertStringContainsString('Chọn người phụ trách để xem bảng giá phù hợp.', $view);
        $this->assertStringNotContainsString('GLOBAL/CUSTOMER · ACTIVE · còn hiệu lực tại ngày xuất.', $view);
        $this->assertStringContainsString('global_user_ids', $view);
        $this->assertStringContainsString('issue-recipient-card', $view);
        $this->assertStringContainsString('updateRecipientCard', $view);
        $this->assertStringNotContainsString('issue-context-summary', $view);
        $this->assertStringContainsString("list.type==='global'", $view);
        $this->assertStringContainsString("list.type==='customer'", $view);
        $this->assertStringNotContainsString('Chọn bảng giá CUSTOMER', $view);
        try { token_get_all(Blade::compileString($view), TOKEN_PARSE); }
        catch (\ParseError $error) { $this->fail('issue-form.blade.php failed Blade compilation: '.$error->getMessage()); }
        $this->addToAssertionCount(1);
    }

    public function test_commission_center_supports_price_list_and_bid_sources(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));
        $model=file_get_contents(base_path('Modules/Pharma/Models/InventoryIssueCommission.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/commissions.blade.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_27_143000_extend_issue_commissions_for_price_lists.php'));

        $this->assertStringContainsString('Trung tâm hoa hồng', $view);
        $documents=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $this->assertStringContainsString("route('admin.pharma.dashboard')", $view);
        $this->assertStringContainsString('← Trung tâm điều hành Pharma', $view);
        $this->assertStringContainsString("route('admin.pharma.dashboard')", $documents);
        $this->assertStringContainsString('← Trung tâm điều hành Pharma', $documents);
        $this->assertStringContainsString("['all'=>'Tất cả','price_list'=>'Theo bảng giá','bid'=>'Hàng thầu']", $view);
        $this->assertStringContainsString('Khách hàng / Bệnh viện', $view);
        $this->assertStringContainsString('Giá trị thu · bảng giá', $view);
        $this->assertStringContainsString('Hoa hồng phát sinh', $view);
        $this->assertStringContainsString('SOURCE_PRICE_LIST', $model);
        $this->assertStringContainsString('snapshotPriceListIssue', $service);
        $this->assertStringContainsString('actual_receivable_price', $service);
        $this->assertStringContainsString("source_type'=>InventoryIssueCommission::SOURCE_PRICE_LIST", $service);
        $this->assertStringContainsString('receivable_price_snapshot', $migration);
        $this->assertStringContainsString('price_list_item_id', $migration);
        $inventoryService=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $this->assertStringContainsString("\$this->commissions->snapshotPostedIssue(\$issue->fresh(['items','deferredSupplies'])", $inventoryService);
        $queryService=file_get_contents(base_path('Modules/Pharma/Services/CommissionQueryService.php'));
        $this->assertStringContainsString("\$filters['source']!=='all'", $queryService);
        $this->assertStringContainsString("pharma_price_list_users", $controller);
        $this->assertStringContainsString("whereNotNull('manager_user_id')", $controller);
        $this->assertStringContainsString("newly configured price list must be selectable", $controller);
        $this->assertStringContainsString('<x-select-search id="commission-user-filter"', $view);
        $this->assertStringContainsString('<x-select-search id="commission-partner-filter"', $view);
        $this->assertStringContainsString('<x-select-search id="commission-medicine-filter"', $view);
        $this->assertStringContainsString('commission-select-all', $view);
        $this->assertStringContainsString('commission-row-checkbox', $view);
        $this->assertStringContainsString('Export Excel đã chọn', $view);
        $this->assertStringContainsString('name="ids[]"', $view);
        $this->assertStringContainsString("resolved_customer_name", $controller);
        $this->assertStringContainsString("\$row->issue?->recipient_name", file_get_contents(base_path('Modules/Pharma/Services/CommissionExcelExportService.php')));
        $this->assertStringContainsString("'ids'=>'nullable|array|max:500'", $controller);
        $this->assertStringContainsString('<div class="w-full space-y-5 px-2 xl:px-3">', $view);
        $this->assertStringContainsString('min-w-[1040px] table-fixed', $view);
        $this->assertStringContainsString('w-[160px] p-3 text-right">Tổng hoa hồng', $view);
        $this->assertStringContainsString("issue_date?->format('d/m/Y') ?: \$row->calculated_at?->format('d/m/Y')", $view);
        $this->assertStringNotContainsString("calculated_at->format('d/m/Y H:i')", $view);
        $this->assertStringContainsString('p-3 text-right font-bold tabular-nums', $view);
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
        $this->assertStringContainsString("in_array(\$doc->status, ['draft','approved'], true)", $documents);
        $this->assertStringContainsString("(\$doc->issue_source ?? 'normal') === 'bid'", $documents);
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
        $this->assertStringNotContainsString("return redirect()->route('admin.pharma.inventory.issues.show',\$issue)", substr($controller, strpos($controller, 'public function showIssue'), strpos($controller, 'public function exportIssuePdf') - strpos($controller, 'public function showIssue')));
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



    public function test_commission_excel_designer_matches_price_list_column_workspace_contract(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/commissions.blade.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $profile=file_get_contents(base_path('Modules/Pharma/Services/CommissionExportProfileService.php'));

        $this->assertStringContainsString('Thiết kế cột Excel',$view);
        $this->assertStringContainsString('Kho dữ liệu',$view);
        $this->assertStringContainsString('Cột sẽ xuất Excel',$view);
        $this->assertStringContainsString('Column Inspector',$view);
        $this->assertStringContainsString('Tiêu đề Excel',$view);
        $this->assertStringContainsString('Kiểu dữ liệu',$view);
        $this->assertStringContainsString('Độ rộng',$view);
        $this->assertStringContainsString('excel_profile',$controller);
        $this->assertStringContainsString('CommissionExportProfileService::normalize',file_get_contents(base_path('Modules/Pharma/Services/CommissionExcelExportService.php')));
        $this->assertStringContainsString("'commission'=>['label'=>'Hoa hồng'",$profile);
        $this->assertStringContainsString("'quantity'=>['label'=>'SL thực xuất'",$profile);
    }


    public function test_commission_excel_designer_controls_stt_header_wrap_and_column_width(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/commissions.blade.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $profile=file_get_contents(base_path('Modules/Pharma/Services/CommissionExportProfileService.php'));

        $this->assertStringContainsString("'stt'=>['label'=>'STT'",$profile);
        $this->assertStringContainsString("'auto_widths'=>array_fill_keys",$profile);
        $this->assertStringContainsString("'wrap_texts'=>array_fill_keys",$profile);
        $this->assertStringContainsString('Auto độ rộng',$view);
        $this->assertStringContainsString('Wrap Text',$view);
        $exporter=file_get_contents(base_path('Modules/Pharma/Services/CommissionExcelExportService.php'));
        $this->assertStringContainsString("setHorizontal('center')",$exporter);
        $this->assertStringContainsString("profile['auto_widths']",$exporter);
        $this->assertStringContainsString("profile['wrap_texts']",$exporter);
    }

}