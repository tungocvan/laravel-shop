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
        $this->assertStringContainsString("'status' => InventoryReceipt::APPROVED", $workspace);
        $this->assertStringContainsString("Chỉ phiếu nhập đã duyệt mới được ghi sổ.", $workspace);
        $this->assertStringNotContainsString('->move(', $workspace);
        $this->assertStringContainsString('postReceipt($locked, $userId)', $workspace);
        $this->assertStringContainsString("InventoryReceipt::APPROVED], true", $inventory);
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
        $this->assertStringContainsString('value="5" data-field="vat_rate"', $create);
    }

    public function test_receipt_list_and_detail_expose_workflow_responsively(): void
    {
        $root = base_path();
        $list = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipts.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-receipt-show.blade.php');

        foreach (['Nháp','Chờ duyệt','Đã duyệt','Đã ghi sổ'] as $label) {
            $this->assertStringContainsString($label, $list);
        }
        foreach (['Sửa','Xóa','Gửi duyệt','Duyệt','Hoàn tác duyệt','Ghi sổ','Hoàn tác ghi sổ'] as $action) {
            $this->assertStringContainsString($action, $detail);
        }
        $this->assertStringContainsString('Chỉ Ghi sổ mới cộng tồn.', $detail);
        $this->assertStringContainsString("number_format((float)\$receipt->total_quantity,0,',','.')", $list);
        $this->assertStringContainsString("number_format((float)\$item->quantity,0,',','.')", $detail);
        $this->assertStringContainsString('xl:hidden', $list);
        $this->assertStringContainsString('xl:block', $list);
        $this->assertStringContainsString('xl:hidden', $detail);
        $this->assertStringContainsString('xl:block', $detail);
        $this->assertStringContainsString('data-pwa-load-more', $list);
        foreach ([$list, $detail] as $view) {
            $this->assertStringContainsString("@section('hide-application-header', true)", $view);
            $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        }
    }
}
