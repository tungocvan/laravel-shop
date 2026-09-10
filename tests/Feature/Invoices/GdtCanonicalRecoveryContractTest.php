<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtCanonicalRecoveryContractTest extends TestCase
{
    #[Test]
    public function historical_purchase_recovery_prefers_missing_detail_before_monthly_list(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString('recoverMissingDetailsFromLocalRange', $service);
        $this->assertStringContainsString("->where('invoice_type', \$invoiceType)", $service);
        $this->assertStringContainsString("->where('detail_status', '!=', 'READY')", $service);
        $this->assertStringContainsString('acquireMissingDetails($invoiceIds', $service);
        $this->assertStringContainsString('$service->recoverMissingDetailsFromLocalRange(', $job);
        $this->assertStringContainsString('RAW canonical sau recovery detail', $job);
    }

    #[Test]
    public function complete_detail_allows_downstream_work_even_when_historical_raw_header_is_pending(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString('$detailReady =', $job);
        $this->assertStringContainsString('&& $detailReady && ! $canonicalReady', $job);
        $this->assertStringContainsString("'source' => 'local_detail_recovery'", $job);
        $this->assertStringContainsString("'source_header_pending' => true", $job);
        $this->assertStringContainsString('Inventory có thể chuẩn hóa từ local', $job);
    }

    #[Test]
    public function transient_gdt_failures_are_backed_off_instead_of_immediate_retries(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString('public array $backoff = [60, 180];', $job);
        $this->assertStringContainsString("'attempt' => \$this->attempts()", $job);
    }
}
