<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceRestoreImpactService;
use Tests\TestCase;

class InvoicesRestoreImpactTest extends TestCase
{
    public function test_preview_never_reports_partner_master_changes(): void
    {
        if (! Schema::hasTable('invoices')) {
            $this->markTestSkipped('Invoices schema is not available.');
        }

        $preview = app(InvoiceRestoreImpactService::class)->preview([]);

        $this->assertSame(0, $preview['partner_master_changes']);
        $this->assertSame(0, $preview['invoices']['delete']);
        $this->assertSame('merge', $preview['recommended_mode']);
    }
}
