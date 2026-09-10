<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceInventoryHandoffContractTest extends TestCase
{
    #[Test]
    public function inventory_contract_is_built_from_gdt_detail_not_pdf_storage(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));

        $this->assertStringContainsString('fetchDetail($invoice)', $factory);
        $this->assertStringContainsString('$rawLines = is_array($detail[\'hdhhdvu\'] ?? null)', $factory);
        $this->assertStringContainsString("'contract_version' => '1.0'", $factory);
        $this->assertStringNotContainsString('storage/app/invoices/pdf', $factory);
        $this->assertStringNotContainsString('downloadInvoice(', $factory);
    }

    #[Test]
    public function handoff_is_explicit_and_only_allows_purchase_invoices(): void
    {
        $handoff = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceInventoryHandoffService.php'));

        $this->assertStringContainsString("invoice_type !== 'purchase'", $handoff);
        $this->assertStringContainsString('InventoryInvoiceIntegrationService::class', $handoff);
        $this->assertStringContainsString('->ingest($this->factory->build($invoice))', $handoff);
    }
}
