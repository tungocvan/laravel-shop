<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtCanonicalPosSyncContractTest extends TestCase
{
    #[Test]
    public function canonical_sync_queries_both_invoice_sources_before_persistence(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());

        $this->assertStringContainsString('fetchInvoicesByMonth($token, $chunkStart, $chunkEnd, $show, $vatIn, true)', $source);
        $this->assertStringContainsString("'/sco-query/invoices/'", $source);
        $this->assertStringContainsString("';ttxly==8'", $source);
        $this->assertStringContainsString("collect(\$all)->unique(", $source);
        $this->assertStringContainsString('persistInvoices($all, $vatIn)', $source);
    }
}
