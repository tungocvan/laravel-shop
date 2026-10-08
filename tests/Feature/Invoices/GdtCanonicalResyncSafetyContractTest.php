<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtCanonicalResyncSafetyContractTest extends TestCase
{
    #[Test]
    public function overlapping_sync_reconciles_legal_identity_without_amount_dependency(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $method = explode('private function findExistingInvoice', $source, 2)[1];
        $method = explode('private function extractTransactionId', $method, 2)[0];

        $this->assertStringContainsString("->where('invoice_number', \$attributes['invoice_number'])", $method);
        $this->assertStringContainsString("->where('symbol', \$attributes['symbol'])", $method);
        $this->assertStringContainsString("->where('issued_date', \$attributes['issued_date'])", $method);
        $this->assertStringContainsString("->where('tax_code', \$attributes['tax_code'])", $method);
        $this->assertStringNotContainsString("->where('total_amount'", $method);
        $this->assertStringNotContainsString("->where('vat_amount'", $method);
        $this->assertStringContainsString("if (\$businessMatches->count() > 1)", $method);
        $this->assertStringContainsString('cần kiểm tra thủ công', $method);
    }

    #[Test]
    public function resync_preserves_annotations_and_reuses_ready_raw_detail(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $persist = explode('private function persistInvoices', $source, 2)[1];
        $persist = explode('private function acquireMissingDetails', $persist, 2)[0];
        $details = explode('private function acquireMissingDetails', $source, 2)[1];
        $details = explode('private function databaseAttributes', $details, 2)[0];

        $this->assertStringContainsString("blank(\$attributes['lookup_code']) && filled(\$invoice->lookup_code)", $persist);
        $this->assertStringContainsString("InvoiceSourceRecord::query()->firstOrCreate", $persist);
        $this->assertStringContainsString("'header_payload' => \$raw", $persist);
        $this->assertStringNotContainsString("'business_classification' =>", $persist);
        $this->assertStringNotContainsString("'business_note' =>", $persist);
        $this->assertStringNotContainsString("'detail_payload' =>", $persist);
        $this->assertStringContainsString('if ($service->storedDetail($invoice) !== null)', $details);
        $this->assertStringContainsString('$service->fetchAndStoreDetail($invoice)', $details);
    }
}
