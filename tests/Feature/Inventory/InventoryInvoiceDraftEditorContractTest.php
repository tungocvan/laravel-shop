<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;

final class InventoryInvoiceDraftEditorContractTest extends TestCase
{
    public function test_draft_editor_uses_modal_flow_and_refreshes_only_draft_receipts(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceDraftEditor.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-draft-editor.blade.php'));
        $page = file_get_contents(base_path('Modules/Inventory/resources/views/pages/invoice-inbox.blade.php'));

        $this->assertStringContainsString("#[On('open-invoice-draft-editor')]", $component);
        $this->assertStringContainsString("\$inbox->receipt === null || \$inbox->receipt->status !== 'DRAFT'", $component);
        $this->assertStringContainsString('InvoiceReceiptProposalService::class', $component);
        $this->assertStringContainsString("'mode' => 'draft_modal_review'", $component);
        $this->assertStringContainsString('Chỉnh sửa phiếu nhập nháp', $view);
        $this->assertStringContainsString('Quy cách đóng gói', $view);
        $this->assertStringContainsString('Cập nhật phiếu nhập nháp', $view);
        $this->assertStringContainsString("window.Livewire?.dispatch('open-invoice-draft-editor')", $page);
        $this->assertStringContainsString('Chỉnh sửa phiếu nhập nháp trước khi xác nhận', $page);
    }

    public function test_item_lookup_uses_x_search_and_server_side_bounded_results(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceDraftEditor.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-draft-editor.blade.php'));
        $page = file_get_contents(base_path('Modules/Inventory/resources/views/pages/invoice-inbox.blade.php'));
        $controller = file_get_contents(base_path('Modules/Inventory/Http/Controllers/InventoryAdminController.php'));
        $routes = file_get_contents(base_path('Modules/Inventory/routes/web.php'));

        $this->assertStringContainsString('<x-search', $view);
        $this->assertStringContainsString('<x-search', $page);
        $this->assertStringContainsString("wire:model.live.debounce.300ms=\"itemSearch\"", $view);
        $this->assertStringContainsString("->limit(20)", $component);
        $this->assertStringContainsString('searchInventoryItems', $controller);
        $this->assertStringContainsString("->limit(20)", $controller);
        $this->assertStringContainsString("name('items.search')", $routes);
        $this->assertStringContainsString("select[wire\\\\:change^=\"assignLine(\"]", $page);
        $this->assertStringContainsString("component.call('assignLine'", $page);
    }

    public function test_shared_item_master_is_protected_while_invoice_created_item_can_update_packaging(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceDraftEditor.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-draft-editor.blade.php'));

        $this->assertStringContainsString('created_from_invoice_inbox_line_id', $component);
        $this->assertStringContainsString("if (\$editableItem)", $component);
        $this->assertStringContainsString("\$metadata['packaging']", $component);
        $this->assertStringContainsString('Mặt hàng dùng chung · bảo vệ master', $view);
        $this->assertStringContainsString('Có thể sửa master data', $view);
        $this->assertStringContainsString('form.package_uom', $view);
        $this->assertStringContainsString('form.package_quantity', $view);
    }
}
