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
        $this->assertMatchesRegularExpression("/~>where\\('invoice_type',\\s*\\$invoiceType\\)/", str_replace('->where', '~>where', $service));
        $this->assertMatchesRegularExpression("/~>where\\('detail_status',\\s*'!=',\\s*'READY'\\)/", str_replace('->where', '~>where', $service));
        $this->assertMatchesRegularExpression('/acquireMissingDetails\\(\\$invoiceIds\\s*,/', $service);
        $this->assertMatchesRegularExpression('/\\$service->recoverMissingDetailsFromLocalRange\\s*\\(/', $job);
        $this->assertStringContainsString('RAW canonical sau recovery detail', $job);
    }

    #[Test]
    public function complete_detail_allows_downstream_work_even_when_historical_raw_header_is_pending(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertMatchesRegularExpression('/\\$detailReady\\s*=/', $job);
        $this->assertMatchesRegularExpression('/&&\\s*\\$detailReady\\s*&&\\s*!\\s*\\$canonicalReady/', $job);
        $this->assertMatchesRegularExpression("/'source'\\s*=>\\s*'local_detail_recovery'/", $job);
        $this->assertMatchesRegularExpression("/'source_header_pending'\\s*=>\\s*true/", $job);
        $this->assertStringContainsString('Inventory có thể chuẩn hóa từ local', $job);
    }

    #[Test]
    public function transient_gdt_failures_are_backed_off_instead_of_immediate_retries(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString('public array $backoff = [60, 180];', $job);
        $this->assertMatchesRegularExpression("/'attempt'\\s*=>\\s*\\$this->attempts\\(\\)/", $job);
    }
}
