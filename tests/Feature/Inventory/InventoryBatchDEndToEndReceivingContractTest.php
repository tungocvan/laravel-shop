<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchDEndToEndReceivingContractTest extends TestCase
{
    #[Test]
    public function receiving_requires_explicit_goods_or_mixed_source_classification(): void
    {
        $eligibility = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceivingEligibilityService.php'));
        $handoff = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceInventoryHandoffService.php'));
        $bulk = file_get_contents(base_path('Modules/Inventory/Services/BulkInvoicePublicationService.php'));

        $this->assertStringContainsString('source_business_classification', $eligibility);
        $this->assertStringContainsString("\$classification === 'GOODS'", $eligibility);
        $this->assertStringContainsString("\$classification === 'MIXED'", $eligibility);
        $this->assertStringContainsString('SERVICE_EXPENSE', $eligibility);
        $this->assertStringContainsString('assertEligible($contract)', $handoff);
        $this->assertStringContainsString('$this->eligibility->assertEligible($contract)', $bulk);
    }

    #[Test]
    public function receipt_proposal_blocks_unsafe_uom_and_required_lot_expiry_and_preserves_operator_review(): void
    {
        $proposal = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));

        $this->assertStringContainsString('ĐVT nguồn khác ĐVT cơ sở', $proposal);
        $this->assertStringContainsString('expiry_tracking', $proposal);
        $this->assertStringContainsString('lot_tracking', $proposal);
        $this->assertStringContainsString("\$receipt !== null && \$receipt->status !== 'DRAFT'", $proposal);
        $this->assertStringContainsString('refresh có chủ đích', $proposal);
        $this->assertStringContainsString('$receipt->lines()->delete()', $proposal);
        $this->assertStringContainsString('receipt đã CONFIRMED luôn bị chặn ở trên', $proposal);
        $this->assertStringContainsString("'source_invoice_identity' => \$inbox->source_invoice_identity", $proposal);
    }

    #[Test]
    public function canonical_receipt_confirmation_remains_the_only_stock_posting_boundary(): void
    {
        $proposal = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));
        $posting = file_get_contents(base_path('Modules/Inventory/Services/ReceiptPostingService.php'));
        $ledgerMigration = file_get_contents(base_path('Modules/Inventory/database/migrations/2026_09_09_140003_create_inventory_ledger_tables.php'));

        $this->assertStringNotContainsString('StockPostingService', $proposal);
        $this->assertStringNotContainsString('->confirm(', $proposal);
        $this->assertStringContainsString('$this->stockPosting->postBatch($movements)', $posting);
        $this->assertStringContainsString("if (\$receipt->status === 'CONFIRMED')", $posting);
        $this->assertStringContainsString("\$table->string('movement_key', 64)->unique()", $ledgerMigration);
        $this->assertStringContainsString("\$table->string('dimension_key', 64)->unique()", $ledgerMigration);
    }
}
