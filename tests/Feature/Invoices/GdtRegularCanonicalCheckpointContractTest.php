<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtRegularCanonicalCheckpointContractTest extends TestCase
{
    #[Test]
    public function regular_canonical_is_persisted_before_pos_request_in_all_source_mode(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));

        $regularFetch = strpos($source, "if (\$source !== 'pos')");
        $checkpoint = strpos($source, '$regularStats = $this->persistInvoices($regular, $vatIn)');
        $posFetch = strpos($source, "\$pos = \$this->fetchInvoicesByMonth(\$token, \$chunkStart, \$chunkEnd, \$show, \$vatIn, true, \$posCaptcha)");
        $combinedExport = strpos($source, '$file = $this->exportExcel($all, $vatIn, $filename)');

        $this->assertNotFalse($regularFetch);
        $this->assertNotFalse($checkpoint);
        $this->assertNotFalse($posFetch);
        $this->assertNotFalse($combinedExport);
        $this->assertTrue($regularFetch < $checkpoint && $checkpoint < $posFetch);
        $this->assertTrue($posFetch < $combinedExport);
        $this->assertStringContainsString('throw $exception;', $source);
    }
}
