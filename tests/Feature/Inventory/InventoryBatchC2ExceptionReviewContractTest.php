<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchC2ExceptionReviewContractTest extends TestCase
{
    #[Test]
    public function inbox_supports_bulk_exception_review_without_bypassing_canonical_matching(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString('public array $selectedLineIds = []', $component);
        $this->assertStringContainsString('public ?int $bulkItemId = null', $component);
        $this->assertStringContainsString('selectAllUnresolved', $component);
        $this->assertStringContainsString('bulkAssignSelected', $component);
        $this->assertStringContainsString('bulkMarkNonStock', $component);
        $this->assertStringContainsString('InventoryItemMatchingService::class', $component);
        $this->assertStringContainsString('->assign($line, $item', $component);
        $this->assertStringContainsString('->markNonStock($line)', $component);
    }

    #[Test]
    public function selected_lines_are_scoped_to_the_current_inbox(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString("->where('inbox_id', $this->selectedInboxId)", $component);
        $this->assertStringContainsString("throw new DomainException('Có dòng đã chọn không thuộc hóa đơn hiện tại.')", $component);
        $this->assertStringContainsString("throw new DomainException('Chọn ít nhất một dòng cần xử lý.')", $component);
    }

    #[Test]
    public function exception_review_ui_has_bulk_mapping_and_non_stock_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString('Chọn tất cả UNRESOLVED', $view);
        $this->assertStringContainsString('Mapping hàng loạt', $view);
        $this->assertStringContainsString('Đánh dấu NON_STOCK', $view);
        $this->assertStringContainsString('wire:model="selectedLineIds"', $view);
        $this->assertStringContainsString('wire:model="bulkItemId"', $view);
        $this->assertStringContainsString('trạng thái tự chuyển READY', $view);
        $this->assertStringNotContainsString('ReceiptPostingService', $view);
    }
}
