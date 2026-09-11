<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchDDraftReceiptCorrectionContractTest extends TestCase
{
    #[Test]
    public function draft_receipt_can_be_explicitly_corrected_before_confirmation(): void
    {
        $service = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString("$receipt !== null && $receipt->status !== 'DRAFT'", $service);
        $this->assertStringContainsString('$receipt->lines()->delete();', $service);
        $this->assertStringContainsString('refresh có chủ đích', $service);

        $this->assertStringContainsString('Chỉnh sửa phiếu nhập nháp trước khi xác nhận', $view);
        $this->assertStringContainsString('Cập nhật phiếu nhập nháp', $view);
        $this->assertStringContainsString('Quay lại chỉnh sửa', $view);
        $this->assertStringContainsString('wire:model="warehouseId"', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.lot_number', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.expiry_date', $view);
        $this->assertStringContainsString('receivingReview.{{ $line->id }}.manufacture_date', $view);
        $this->assertStringContainsString('wire:click="createDraftReceipt"', $view);
        $this->assertStringContainsString('wire:click="confirmReceipt"', $view);
    }
}
