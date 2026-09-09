<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchC2BulkPublicationContractTest extends TestCase
{
    #[Test]
    public function staged_snapshot_is_the_preferred_publication_source(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));

        $this->assertStringContainsString('buildFromSnapshot(InvoiceInventorySnapshot $snapshot)', $factory);
        $this->assertStringContainsString("\$snapshot->status !== 'NORMALIZED'", $factory);
        $this->assertStringContainsString("'staging_line_id' => \$line->id", $factory);
        $this->assertStringContainsString("'raw_description' => \$line->raw_description", $factory);
        $this->assertStringContainsString("'package_spec' => \$line->package_spec", $factory);
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
    public function intake_ui_exposes_bulk_publish_and_draft_without_confirm_action(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/ReceivingIntakeWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/receiving-intake-workspace.blade.php'));

        $this->assertStringContainsString('publishSelected', $component);
        $this->assertStringContainsString('createDraftReceipts', $component);
        $this->assertStringContainsString('selectedSnapshots', $component);
        $this->assertStringContainsString('Publish sang Inbox', $view);
        $this->assertStringContainsString('Tạo Receipt DRAFT', $view);
        $this->assertStringContainsString('Không có thao tác xác nhận tồn kho tại màn hình này.', $view);
        $this->assertStringNotContainsString('ReceiptPostingService', $component.$view);
    }
}
