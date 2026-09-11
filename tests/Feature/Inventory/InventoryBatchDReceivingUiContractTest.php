<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchDReceivingUiContractTest extends TestCase
{
    #[Test]
    public function receiving_entry_is_a_period_filtered_source_queue_instead_of_a_manual_picker(): void
    {
        $queue = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceReceivingSourceQueue.php'));
        $queueView = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-receiving-source-queue.blade.php'));
        $page = file_get_contents(base_path('Modules/Inventory/resources/views/pages/invoice-inbox.blade.php'));
        $sourceQuery = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/PurchaseInvoiceInventoryQueryService.php'));

        $this->assertStringContainsString('forReceivingQueue', $sourceQuery);
        $this->assertStringContainsString("whereYear('issued_date', \$year)", $sourceQuery);
        $this->assertStringContainsString("whereMonth('issued_date', \$month)", $sourceQuery);
        $this->assertStringContainsString('public int $year;', $queue);
        $this->assertStringContainsString('public int $month;', $queue);
        $this->assertStringContainsString('hasUsableDetail', $queue);
        $this->assertStringContainsString("['GOODS', 'MIXED']", $queue);
        $this->assertStringContainsString('startReceiving', $queue);
        $this->assertStringContainsString('InvoiceInventoryHandoffService::class', $queue);
        $this->assertStringContainsString('Danh sách hóa đơn mua hàng', $queueView);
        $this->assertStringContainsString('Chờ RAW detail', $queueView);
        $this->assertStringContainsString('Nhập kho →', $queueView);
        $this->assertStringContainsString('Tiếp tục →', $queueView);
        $this->assertStringContainsString('invoice-receiving-source-queue', $page);
        $this->assertStringContainsString(':selected-inbox-id="(int) request(\'inbox\')"', $page);
    }

    #[Test]
    public function inbox_exposes_explicit_item_review_and_allows_revision_before_draft(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('beginCreateItem', $component);
        $this->assertStringContainsString('beginEditItem', $component);
        $this->assertStringContainsString('saveStandaloneItem', $component);
        $this->assertStringContainsString('public ?int $editingItemId = null;', $component);
        $this->assertStringContainsString("'creation_mode' => 'explicit_admin_review'", $component);
        $this->assertStringContainsString('created_from_invoice_inbox_line_id', $component);
        $this->assertStringNotContainsString('firstOrCreate', $component);
        $this->assertStringContainsString('Tạo mặt hàng mới', $view);
        $this->assertStringContainsString('Sửa mặt hàng', $view);
        $this->assertStringContainsString('Đổi sang mặt hàng khác...', $view);
        $this->assertStringContainsString('itemForm.lot_tracking', $view);
        $this->assertStringContainsString('itemForm.expiry_tracking', $view);
        $this->assertStringContainsString('itemForm.base_uom', $view);
    }

    #[Test]
    public function create_item_modal_carries_invoice_lot_and_expiry_into_receiving_review(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString("'lot_number' => (string) (\$line->lot_number ?? '')", $component);
        $this->assertStringContainsString("'expiry_date' => \$line->expiry_date?->format('Y-m-d') ?? ''", $component);
        $this->assertStringContainsString("'lot_number' => filled(\$data['lot_number']", $component);
        $this->assertStringContainsString("'expiry_date' => \$expiryDate", $component);
        $this->assertStringContainsString('Thông tin lô của lần nhập này', $view);
        $this->assertStringContainsString('itemForm.lot_number', $view);
        $this->assertStringContainsString('itemForm.expiry_date', $view);
        $this->assertStringContainsString('Hệ thống điền sẵn từ hóa đơn nếu có', $view);
    }

    #[Test]
    public function manufacture_date_is_optional_and_only_shown_when_user_enables_it(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString("'include_manufacture_date' => \$line->manufacture_date !== null", $component);
        $this->assertStringContainsString("\$key.'.manufacture_date' => ['nullable', 'date']", $component);
        $this->assertStringContainsString("if (! (bool) (\$data['include_manufacture_date'] ?? false))", $component);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.include_manufacture_date', $view);
        $this->assertStringContainsString('itemForm.include_manufacture_date', $view);
        $this->assertStringContainsString('Ngày sản xuất là tùy chọn', $view);
        $this->assertStringContainsString('Không dùng thông tin “NSX: Việt Nam” làm ngày sản xuất.', $view);
    }

    #[Test]
    public function stock_lines_validate_required_receiving_fields_before_draft_creation(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('public array $receivingReview = [];', $component);
        $this->assertStringContainsString('saveReceivingReview', $component);
        $this->assertStringContainsString('validateReceivingReview', $component);
        $this->assertStringContainsString('persistReceivingReview', $component);
        $this->assertStringContainsString('$item->lot_tracking ? \'required\' : \'nullable\'', $component);
        $this->assertStringContainsString('$item->expiry_tracking ? \'required\' : \'nullable\'', $component);
        $this->assertStringContainsString('Vui lòng nhập số lô', $component);
        $this->assertStringContainsString('Vui lòng nhập HSD', $component);
        $this->assertStringContainsString('Hãy chọn kho nhận trước khi tạo phiếu nhập DRAFT.', $component);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.base_quantity', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.base_uom', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.conversion_factor', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.lot_number', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.manufacture_date', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.expiry_date', $view);
        $this->assertStringContainsString('Lưu thông tin mặt hàng', $view);
    }

    #[Test]
    public function item_mapping_accepts_livewire_string_values_without_type_error(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString('public function assignLine(int $lineId, $itemId = null): void', $component);
        $this->assertStringContainsString("trim((string) \$itemId) === ''", $component);
        $this->assertStringContainsString('ctype_digit((string) $itemId)', $component);
        $this->assertStringContainsString('$itemId = (int) $itemId;', $component);
    }

    #[Test]
    public function validation_errors_are_user_facing_instead_of_reported_as_system_failures(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString('catch (ValidationException $exception)', $component);
        $this->assertStringContainsString('collect($exception->errors())->flatten()->first()', $component);
        $this->assertStringContainsString('Dữ liệu chưa hợp lệ. Vui lòng kiểm tra lại các trường bắt buộc.', $component);
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
        $this->assertStringContainsString('Xác nhận nhập kho', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
    }

    #[Test]
    public function confirmed_receiving_keeps_source_to_movement_to_balance_trace_in_backend(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('StockMovement::query()', $component);
        $this->assertStringContainsString('StockBalance::query()', $component);
        $this->assertStringContainsString("->where('document_type', 'receipt')", $component);
        $this->assertStringContainsString('source_invoice_identity', $component);
        $this->assertStringContainsString('Nhập kho thành công', $view);
        $this->assertStringContainsString('lịch sử kho để truy vết', $view);
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
        $this->assertStringContainsString('max-w-4xl', $view);
        $this->assertStringContainsString('max-h-[92vh]', $view);
    }
}
