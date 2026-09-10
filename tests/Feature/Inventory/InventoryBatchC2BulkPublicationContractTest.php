<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchC2BulkPublicationContractTest extends TestCase
{
    #[Test]
    public function staged_snapshot_is_the_preferred_publication_source_but_canonical_raw_stays_in_invoices(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));

        $this->assertStringContainsString('buildFromSnapshot(InvoiceInventorySnapshot $snapshot)', $factory);
        $this->assertStringContainsString("\$snapshot->status !== 'NORMALIZED'", $factory);
        $this->assertStringContainsString('$this->gdtDetailService->fetchDetail($invoice)', $factory);
        $this->assertStringContainsString("'staging_line_id' => \$line->id", $factory);
        $this->assertStringContainsString("'raw_description' => \$line->raw_description", $factory);
        $this->assertStringContainsString("'source_business_classification'", $factory);
        $this->assertStringNotContainsString('fetchAndStoreDetail(', $factory);
    }

    #[Test]
    public function bulk_publication_reuses_inventory_contract_and_only_creates_drafts(): void
    {
        $service = file_get_contents(base_path('Modules/Inventory/Services/BulkInvoicePublicationService.php'));

        $this->assertStringContainsString("->where('status', 'NORMALIZED')", $service);
        $this->assertStringContainsString('buildFromSnapshot($snapshot)', $service);
        $this->assertStringContainsString('$this->integration->ingest', $service);
        $this->assertStringContainsString('$this->receipts->createOrRefresh', $service);
        $this->assertStringNotContainsString('ReceiptPostingService', $service);
        $this->assertStringNotContainsString('->confirm(', $service);
    }

    #[Test]
    public function receipt_proposal_preserves_each_stock_line_and_its_lot_expiry_without_merging(): void
    {
        $service = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));

        $this->assertStringContainsString("\$stockLines = \$inbox->lines->where('classification', 'STOCK')->values()", $service);
        $this->assertStringContainsString('foreach ($stockLines as $index => $line)', $service);
        $this->assertStringContainsString('ReceiptLine::query()->create([', $service);
        $this->assertStringContainsString("'source_line_key' => \$line->source_line_key", $service);
        $this->assertStringContainsString("'lot_number' => \$line->lot_number", $service);
        $this->assertStringContainsString("'expiry_date' => \$line->expiry_date", $service);
        $this->assertStringNotContainsString('groupBy(', $service);
    }

    #[Test]
    public function receipt_proposal_rejects_all_non_stock_and_unmapped_stock_and_never_posts_stock(): void
    {
        $service = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));

        $this->assertStringContainsString("if (\$stockLines->isEmpty())", $service);
        $this->assertStringContainsString('Hóa đơn không có dòng STOCK để tạo phiếu nhập.', $service);
        $this->assertStringContainsString("\$line->inventory_item_id === null", $service);
        $this->assertStringContainsString("\$line->base_quantity === null", $service);
        $this->assertStringContainsString("\$line->base_uom === null", $service);
        $this->assertStringContainsString("'status' => 'DRAFT'", $service);
        $this->assertStringNotContainsString('ReceiptPostingService', $service);
        $this->assertStringNotContainsString('->confirm(', $service);
        $this->assertStringNotContainsString('InventoryMovement', $service);
        $this->assertStringNotContainsString('InventoryBalance', $service);
    }

    #[Test]
    public function intake_ui_only_normalizes_persisted_raw_and_exposes_no_gdt_or_confirm_action(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/ReceivingIntakeWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/receiving-intake-workspace.blade.php'));
        $page = file_get_contents(base_path('Modules/Inventory/resources/views/pages/intake.blade.php'));

        $this->assertStringContainsString('publishSelected', $component);
        $this->assertStringContainsString('createDraftReceipts', $component);
        $this->assertStringContainsString('selectedSnapshots', $component);
        $this->assertStringContainsString("'raw_ready'", $component);
        $this->assertStringContainsString("'raw_missing'", $component);
        $this->assertStringContainsString('Inventory không gọi GDT.', $component);
        $this->assertStringContainsString('Chuẩn hóa RAW đã lưu', $view);
        $this->assertStringContainsString('Đồng bộ nguồn tại Invoices', $view);
        $this->assertStringContainsString('Publish sang Inbox', $view);
        $this->assertStringContainsString('Tạo Receipt DRAFT', $view);
        $this->assertStringContainsString('Không có thao tác xác nhận tồn kho tại màn hình này.', $view);
        $this->assertStringContainsString('<livewire:inventory.receiving-intake-workspace />', $page);
        $this->assertStringNotContainsString('inventory::receiving-intake-workspace', $page);
        $this->assertStringNotContainsString('GdtApiService', $component.$view);
        $this->assertStringNotContainsString('GdtInvoiceService', $component.$view);
        $this->assertStringNotContainsString('GdtPdfService', $component.$view);
        $this->assertStringNotContainsString('ReceiptPostingService', $component.$view);
    }
}
