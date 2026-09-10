<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchDReceivingUiContractTest extends TestCase
{
    #[Test]
    public function inbox_exposes_explicit_item_review_instead_of_silent_creation(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('beginCreateItem', $component);
        $this->assertStringContainsString('saveStandaloneItem', $component);
        $this->assertStringContainsString("'creation_mode' => 'explicit_admin_review'", $component);
        $this->assertStringNotContainsString('createStandaloneItem', $component.$view);
        $this->assertStringNotContainsString('firstOrCreate', $component);
        $this->assertStringContainsString('Tạo InventoryItem sau khi review', $view);
        $this->assertStringContainsString('itemForm.lot_tracking', $view);
        $this->assertStringContainsString('itemForm.expiry_tracking', $view);
        $this->assertStringContainsString('itemForm.base_uom', $view);
    }

    #[Test]
    public function stock_lines_can_review_receiving_fields_before_draft_creation(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('public array $receivingReview = [];', $component);
        $this->assertStringContainsString('saveReceivingReview', $component);
        $this->assertStringContainsString("'mode' => 'explicit_admin_review'", $component);
        $this->assertStringContainsString('Mặt hàng theo dõi HSD: cần nhập hạn dùng trước khi tạo DRAFT.', $component);
        $this->assertStringContainsString('Mặt hàng theo dõi lô: cần nhập số lô trước khi tạo DRAFT.', $component);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.base_quantity', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.base_uom', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.conversion_factor', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.lot_number', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.manufacture_date', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.expiry_date', $view);
        $this->assertStringContainsString('Lưu review dòng', $view);
    }

    #[Test]
    public function invoice_inbox_confirms_only_through_canonical_receipt_posting_service(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('ReceiptPostingService', $component);
        $this->assertStringContainsString('app(ReceiptPostingService::class)->confirm', $component);
        $this->assertStringContainsString('inventory.receipt.confirm', $component);
        $this->assertStringNotContainsString('StockPostingService', $component);
        $this->assertStringContainsString('askConfirmReceipt', $view);
        $this->assertStringContainsString('Xác nhận và cộng tồn', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
    }

    #[Test]
    public function confirmed_receiving_exposes_source_to_movement_to_balance_trace(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('StockMovement::query()', $component);
        $this->assertStringContainsString('StockBalance::query()', $component);
        $this->assertStringContainsString("->where('document_type', 'receipt')", $component);
        $this->assertStringContainsString('source_invoice_identity', $view);
        $this->assertStringContainsString('Audit sau xác nhận', $view);
        $this->assertStringContainsString('Movement → Balance', $view);
    }

    #[Test]
    public function batch_d_ui_keeps_bounded_pagination_and_admin_form_boundaries(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('private const PAGE_SIZES = [10, 25, 50, 100]', $component);
        $this->assertStringContainsString("links('Inventory::vendor.pagination.admin-inventory')", $view);
        $this->assertStringContainsString('border border-gray-300 bg-white', $view);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $view);
        $this->assertStringNotContainsString('<option value="all">All</option>', $view);
    }
}
