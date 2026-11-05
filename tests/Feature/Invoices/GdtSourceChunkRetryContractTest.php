<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtSourceChunkRetryContractTest extends TestCase
{
    #[Test]
    public function all_source_retry_reuses_only_complete_scoped_monthly_payloads(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString("string \$source = 'all', ?string \$syncId = null", $service);
        $this->assertStringContainsString("'invoices:gdt-source-chunk:'", $service);
        $this->assertStringContainsString("\$syncId, \$vatIn ? 'purchase' : 'sold'", $service);
        $this->assertStringContainsString("\$chunkStart->toDateString(), \$chunkEnd->toDateString()", $service);
        $this->assertStringContainsString("isset(\$checkpoint['regular'])", $service);
        $this->assertStringContainsString("isset(\$checkpoint['pos'])", $service);
        $this->assertStringContainsString("\$checkpoint['regular'] = \$regular;", $service);
        $this->assertStringContainsString("\$checkpoint['pos'] = \$pos;", $service);
        $this->assertStringContainsString("}, \$this->vatIn, 'all', \$this->syncId);", $job);
        $this->assertStringContainsString("throw \$exception;", $service);
        $this->assertStringContainsString("\$file = \$this->exportExcel(\$all, \$vatIn, \$filename)", $service);
    }
}
