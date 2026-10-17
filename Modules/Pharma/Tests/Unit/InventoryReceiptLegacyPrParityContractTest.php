<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class InventoryReceiptLegacyPrParityContractTest extends TestCase
{
    public function test_legacy_receipt_workspace_is_replaced_by_scoped_document_routes(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString('/receipts/settings/document', $routes);
        $this->assertStringContainsString('receipts.pdf.invoice.export', $routes);
        $this->assertStringContainsString('receipts.pdf.cost.export', $routes);
        $this->assertStringContainsString('receipts.print.invoice', $routes);
        $this->assertStringContainsString('receipts.print.cost', $routes);
        $this->assertStringContainsString('can:view_pharma_inventory_costs', $routes);
        $this->assertStringNotContainsString("name('receipts.pdf')", $routes);
        $this->assertStringNotContainsString("name('receipts.print')", $routes);
    }

    public function test_receipt_documents_use_separate_settings_and_posted_gate(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/InventoryReceiptDocumentSetting.php'));
        $documents = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));

        $this->assertStringContainsString('pharma_inventory_receipt_document_settings', $model);
        $this->assertStringContainsString('cost_document_title', $model);
        $this->assertStringContainsString('invoice_document_title', $model);
        $this->assertStringContainsString('show_invoice_unit_price', $model);
        $this->assertStringContainsString('InventoryReceipt::POSTED', $documents);
        $this->assertStringContainsString("receipts.pdf.invoice", $documents);
        $this->assertStringContainsString("receipts.pdf.cost", $documents);
        $this->assertStringContainsString("@can('view_pharma_inventory_costs')", $documents);
    }
}
