<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtPosDetailEndpointContractTest extends TestCase
{
    #[Test]
    public function raw_detail_uses_sco_endpoint_for_machine_register_invoices(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Services/GdtPdfService.php'));
        $this->assertStringContainsString('$this->detailEndpoint($invoice)', $source);
        $this->assertStringContainsString("'/sco-query/invoices/detail'", $source);
        $this->assertStringContainsString("'/query/invoices/detail'", $source);
        $this->assertStringContainsString("array_key_exists('hthdon', \$header)", $source);
        $this->assertStringContainsString("(int) \$header['hthdon'] === 5", $source);
        $this->assertStringContainsString("array_key_exists('ttxly', \$header)", $source);
        $this->assertStringContainsString("preg_match('/^[CK][0-9]{2}M[A-Z0-9]+$/i', \$series)", $source);
        $this->assertStringContainsString("'detail_status' => 'READY'", $source);
    }
}
