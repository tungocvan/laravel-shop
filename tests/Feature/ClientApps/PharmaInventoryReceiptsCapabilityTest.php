<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryReceiptsCapabilityTest extends TestCase
{
    public function test_receipt_workflow_is_permission_scoped_and_routed(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');

        foreach (['submit','undo-submit','approve','undo-approval','post','revert'] as $action) {
            $this->assertStringContainsString("/inventory/receipts/{receipt}/{$action}", $routes);
        }
        $this->assertStringContainsString("Route::put('/inventory/receipts/{receipt}'", $routes);
        $this->assertStringContainsString("Route::delete('/inventory/receipts/{receipt}'", $routes);
        foreach (['client.pharma.inventory.receipts.create','client.pharma.inventory.receipts.submit','client.pharma.inventory.receipts.approve','client.pharma.inventory.receipts.post'] as $permission) {
            $this->assertStringContainsString("'permission' => '{$permission}'", $manifest);
            $this->assertStringContainsString("userCan(\$user, '{$permission}')", $controller);
        }
    }

    public function test_receipt_state_machine_is_locked_and_only_posting_moves_stock(): void
    {
        $root = base_path();
        $model = file_get_contents($root.'/Modules/Pharma/Models/InventoryReceipt.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryReceiptWorkspace.php');
        $inventory = file_get_contents($root.'/Modules/Pharma/Services/InventoryService.php');
        $migration = file_get_contents($root.'/Modules/Pharma/database/migrations/2026_10_02_143000_add_approval_lifecycle_to_pharma_inventory_receipts.php');

        $this->assertStringContainsString("PENDING_APPROVAL='pending_approval'", $model);
        $this->assertStringContainsString("APPROVED='approved'", $model);
        $this->assertStringContainsString("lockForUpdate()", $workspace);
        $this->assertStringContainsString("'status' => InventoryReceipt::PENDING_APPROVAL", $workspace);
        $this->assertStringContainsString("[InventoryReceipt::DRAFT, InventoryReceipt::PENDING_APPROVAL]", $workspace);
        $this->assertStringContainsString("'status' => InventoryReceipt::APPROVED", $workspace);
        $this->assertStringContainsString("'status' => InventoryReceipt::DRAFT", $workspace);
        $this->assertStringContainsString("Chỉ phiếu nhập đã duyệt mới được ghi sổ.", $workspace);
        $this->assertStringNotContainsString('->move(', $workspace);
        $this->assertStringContainsString('postReceipt($locked, $userId)', $workspace);
        $this->assertStringContainsString("if (\$receipt->status !== InventoryReceipt::APPROVED)", $inventory);
        $this->assertStringNotContainsString("[InventoryReceipt::DRAFT, InventoryReceipt::APPROVED]", $inventory);
        $this->assertStringContainsString("\$receipt->approved_at ? InventoryReceipt::APPROVED : InventoryReceipt::DRAFT", $inventory);
        foreach (['submitted_by','submitted_at','approved_by','approved_at'] as $column) {
            $this->assertStringContainsString("'{$column}'", $migration);
        }
    }

    public function test_receipt_draft_authoring_and_editing_never_move_stock(): void
    {
        $root = base_path();
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryReceiptWorkspace.php');
        $create = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-create.blade.php');

        $this->assertStringContainsString('createDraft($data, (int) $user->id)', $controller);
        $this->assertStringContainsString('updateDraft($visibleReceipt, $data)', $controller);
        $this->assertStringContainsString("'status' => InventoryReceipt::DRAFT", $workspace);
        $this->assertStringContainsString("items()->createMany(\$data['items'])", $workspace);
        $this->assertStringContainsString('Lưu nháp không làm thay đổi tồn kho', $create);
        $this->assertStringContainsString("route('client.pharma.inventory.receipts.update',\$receipt)", $create);
        $this->assertStringContainsString("@method('PUT')", $create);
        $this->assertStringContainsString('data-receipt-combobox', $create);
        $this->assertStringContainsString('placeholder="Tìm tên nhà cung cấp / MST..."', $create);
        $this->assertStringContainsString('placeholder="Tìm tên thuốc / mã thuốc / hoạt chất..."', $create);
        $this->assertStringContainsString("old('invoice_date', \$editing ? (\$receipt->invoice_date?->format('Y-m-d') ?? now()->toDateString()) : now()->toDateString())", $create);
        $this->assertStringContainsString('data-number-display data-scale="3"', $create);
        $this->assertStringContainsString('data-number-display data-scale="4"', $create);
        $this->assertStringContainsString('Giá vốn TB:', $create);
        $this->assertStringContainsString('id="toggle-receipt-invoice"', $create);
        $this->assertStringContainsString('id="receipt-invoice-fields" class="mt-3 hidden"', $create);
        $this->assertStringContainsString('name="vat_rate"', $create);
        $this->assertStringContainsString('Một phiếu nhập chỉ áp dụng một mức VAT cho toàn bộ hàng nhập.', $create);
        $this->assertStringNotContainsString('data-field="vat_rate"', $create);
        $this->assertStringContainsString("'vat_rate' => ['required', 'numeric', 'min:0', 'max:100']", $controller);
        $this->assertStringContainsString("'vat_rate' => (float) \$data['vat_rate']", $workspace);
        $this->assertStringContainsString('name="invoice_symbol"', $create);
        $this->assertStringContainsString('Ký hiệu hóa đơn', $create);
        $this->assertStringContainsString('Giá nhập / Giá vốn *', $create);
        $this->assertStringContainsString('Giá xuất HĐ chưa VAT', $create);
        $this->assertStringContainsString('data-field="invoice_unit_price_ex_vat"', $create);
        $this->assertStringContainsString("'invoice_symbol' => ['nullable', 'string', 'max:100']", $controller);
        $this->assertStringContainsString("'items.*.invoice_unit_price_ex_vat' => ['nullable', 'numeric', 'min:0']", $controller);
        $this->assertStringContainsString("'invoice_symbol' => \$data['invoice_symbol'] ?? null", $workspace);
    }

    public function test_receipt_list_and_detail_expose_workflow_responsively(): void
    {
        $root = base_path();
        $list = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipts.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-show.blade.php');

        foreach (['Nháp','Đã duyệt','Đã ghi sổ'] as $label) {
            $this->assertStringContainsString($label, $list);
        }
        foreach (['Sửa','Xóa','Phê duyệt','Hoàn tác phê duyệt','Ghi sổ','Hoàn tác ghi sổ'] as $action) {
            $this->assertStringContainsString($action, $detail);
        }
        $this->assertStringContainsString('Chỉ Ghi sổ mới cộng tồn', $detail);
        $this->assertStringContainsString('Hoàn tác ghi sổ sẽ trừ lại đúng số lượng đã nhập', $detail);
        $this->assertStringContainsString('Ký hiệu', $detail);
        $this->assertStringContainsString('Giá xuất HĐ chưa VAT', $detail);
        $this->assertStringContainsString('Tổng giá trị', $detail);
        $this->assertStringContainsString('Giá vốn', $detail);
        $this->assertStringContainsString('Hóa đơn chưa VAT', $detail);
        $this->assertStringContainsString('sm:grid-cols-[9rem_minmax(0,1fr)]', $detail);
        $this->assertStringContainsString('grid grid-cols-3 gap-2', $detail);
        $this->assertStringContainsString('Hóa đơn', $detail);
        $this->assertStringContainsString('Tham khảo chứng từ', $detail);
        $this->assertStringContainsString('Số HĐ', $detail);
        $this->assertStringContainsString('Ngày HĐ', $detail);
        $this->assertStringContainsString("number_format((float)\$receipt->total_quantity,0,',','.')", $list);
        $this->assertStringContainsString("number_format((float)\$item->quantity,0,',','.')", $detail);
        $this->assertStringContainsString('xl:hidden', $list);
        $this->assertStringContainsString('xl:block', $list);
        $this->assertStringContainsString('xl:hidden', $detail);
        $this->assertStringContainsString('xl:block', $detail);
        $this->assertStringContainsString('data-pwa-load-more', $list);
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryReceiptWorkspace.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $this->assertStringContainsString('public function statusCounts(?string $search = null): array', $workspace);
        $this->assertStringContainsString("selectRaw('status, COUNT(*) as aggregate')", $workspace);
        $this->assertStringContainsString("'statusCounts' => \$workspace->statusCounts(\$validated['q'] ?? null)", $controller);
        $this->assertStringContainsString("[''=>'Tất cả','draft'=>'Nháp','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy']", $list);
        $this->assertStringContainsString('data-receipt-status-bar', $list);
        $this->assertStringContainsString('data-disabled-status', $list);
        $this->assertStringContainsString('aria-disabled="true"', $list);
        $this->assertStringContainsString('Xóa bộ lọc', $list);
        $this->assertStringContainsString('data-pwa-debounced-search="600"', $list);
        $this->assertStringContainsString('data-pwa-search-clear-button="#receipt-search-input"', $list);
        $this->assertStringContainsString('data-receipt-card', $list);
        $this->assertStringContainsString('motion-reduce:transform-none', $list);
        $this->assertStringContainsString('data-pwa-load-more-target="#receipt-mobile-list"', $list);
        foreach ([$list, $detail] as $view) {
            $this->assertStringContainsString("@section('hide-application-header', true)", $view);
            $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        }
    }

    public function test_receipt_admin_and_pwa_share_approval_posting_contract(): void
    {
        $root = base_path();
        $adminRoutes = file_get_contents($root.'/Modules/Pharma/routes/web.php');
        $adminController = file_get_contents($root.'/Modules/Pharma/Http/Controllers/InventoryController.php');
        $adminList = file_get_contents($root.'/Modules/Pharma/resources/views/pages/inventory/documents.blade.php');
        $adminDetail = file_get_contents($root.'/Modules/Pharma/resources/views/pages/inventory/receipt-show.blade.php');
        $inventory = file_get_contents($root.'/Modules/Pharma/Services/InventoryService.php');

        $this->assertStringContainsString("name('receipts.approve')", $adminRoutes);
        $this->assertStringContainsString("name('receipts.undo-approval')", $adminRoutes);
        $this->assertStringContainsString('function approveReceipt(', $adminController);
        $this->assertStringContainsString('function undoReceiptApproval(', $adminController);
        $this->assertStringContainsString("if (\$receipt->status !== InventoryReceipt::APPROVED)", $inventory);
        $this->assertStringNotContainsString("[InventoryReceipt::DRAFT, InventoryReceipt::APPROVED]", $inventory);
        $this->assertStringContainsString("'type'=>'receipt_reversal'", $inventory);
        $this->assertStringContainsString("'quantity_delta'=>-\$quantity", $inventory);
        $this->assertStringContainsString("status'=>\$receipt->approved_at ? InventoryReceipt::APPROVED : InventoryReceipt::DRAFT", $inventory);
        foreach (['Phê duyệt','Hoàn tác phê duyệt','Ghi sổ','Hoàn tác ghi sổ'] as $action) {
            $this->assertStringContainsString($action, $adminList.$adminDetail);
        }
        $this->assertStringContainsString('Chỉ Ghi sổ mới cộng tồn', $adminDetail);
        $this->assertStringContainsString('Hoàn tác ghi sổ sẽ trừ lại đúng số lượng đã nhập', $adminDetail);
    }

    public function test_receipt_invoice_reference_schema_is_additive_and_nullable(): void
    {
        $root = base_path();
        $migration = file_get_contents($root.'/Modules/Pharma/database/migrations/2026_10_02_150000_add_invoice_reference_fields_to_pharma_inventory_receipts.php');
        $item = file_get_contents($root.'/Modules/Pharma/Models/InventoryReceiptItem.php');

        $this->assertStringContainsString("string('invoice_symbol', 100)->nullable()", $migration);
        $this->assertStringContainsString("decimal('invoice_unit_price_ex_vat', 18, 4)->nullable()", $migration);
        $this->assertStringContainsString("'invoice_unit_price_ex_vat'=>'decimal:4'", $item);
    }
}
