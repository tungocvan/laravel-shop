<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtCanonicalRecoveryContractTest extends TestCase
{
    #[Test]
    public function historical_detail_recovery_prefers_missing_detail_before_monthly_list_for_both_directions(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString('recoverMissingDetailsFromLocalRange', $service);
        $this->assertMatchesRegularExpression('/~>where\(\'invoice_type\',\s*\$invoiceType\)/', str_replace('->where', '~>where', $service));
        $this->assertMatchesRegularExpression('/~>where\(\'detail_status\',\s*\'!=\',\s*\'READY\'\)/', str_replace('->where', '~>where', $service));
        $this->assertMatchesRegularExpression('/acquireMissingDetails\(\$invoiceIds\s*,/', $service);
        $this->assertMatchesRegularExpression('/\$service->recoverMissingDetailsFromLocalRange\s*\(/', $job);
        $this->assertStringContainsString(',$this->vatIn);', $job);
        $this->assertStringNotContainsString('if($this->vatIn&&(int)$sourceCoverage', $job);
        $this->assertStringContainsString('Phát hiện detail còn thiếu; ưu tiên recovery từ invoice local', $job);
        $this->assertStringContainsString('RAW canonical sau recovery detail', $job);
    }

    #[Test]
    public function partial_recovery_with_complete_headers_schedules_background_recovery_instead_of_reloading_list(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));
        $recovery = file_get_contents(base_path('Modules/Invoices/Jobs/RecoverMissingGdtDetailsJob.php'));

        $this->assertStringContainsString('$headersComplete=', $job);
        $this->assertStringContainsString('$remainingDetail=', $job);
        $this->assertStringContainsString('$this->scheduleAutoRecovery($remainingDetail);', $job);
        $this->assertStringContainsString('RecoverMissingGdtDetailsJob::dispatch(', $job);
        $this->assertStringContainsString('không cần bấm đồng bộ lại', $job);
        $this->assertStringContainsString('recoverMissingDetailsFromLocalRange(', $recovery);
        $this->assertStringContainsString("config('invoices.gdt.auto_recovery_max_rounds', 6)", $recovery);
        $this->assertStringContainsString("config('invoices.gdt.auto_recovery_backoff_seconds', [60, 180, 300, 600, 900])", $recovery);
        $this->assertStringContainsString("->delay(now()->addSeconds(\$delay));", $recovery);
        $this->assertStringContainsString("'auto_recovery_pending' => true", $recovery);
    }

    #[Test]
    public function automatic_recovery_stops_when_complete_token_expires_or_round_limit_is_reached(): void
    {
        $recovery = file_get_contents(base_path('Modules/Invoices/Jobs/RecoverMissingGdtDetailsJob.php'));

        $this->assertStringContainsString('if ($missingAfter === 0)', $recovery);
        $this->assertStringContainsString('if (! Cache::has(', $recovery);
        $this->assertStringContainsString('phiên đăng nhập GDT đã hết hạn', $recovery);
        $this->assertStringContainsString('if ($this->round >= $maxRounds)', $recovery);
        $this->assertStringContainsString("'missing_detail' => 0", $recovery);
    }

    #[Test]
    public function complete_detail_allows_downstream_work_even_when_historical_raw_header_is_pending(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertMatchesRegularExpression('/\$detailReady\s*=/', $job);
        $this->assertMatchesRegularExpression('/&&\s*\$detailReady\s*&&\s*!\s*\$canonicalReady/', $job);
        $this->assertMatchesRegularExpression('/\'source\'\s*=>\s*\'local_detail_recovery\'/', $job);
        $this->assertMatchesRegularExpression('/\'source_header_pending\'\s*=>\s*true/', $job);
        $this->assertStringContainsString("'direction'=>\$this->vatIn?'vat_in':'vat_out'", $job);
        $this->assertStringContainsString('Dữ liệu detail local đã sẵn sàng', $job);
    }

    #[Test]
    public function transient_gdt_failures_are_backed_off_instead_of_immediate_retries(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));
        $pdfService = file_get_contents(base_path('Modules/Invoices/Services/GdtPdfService.php'));

        $this->assertStringContainsString('public array $backoff = [60, 180];', $job);
        $this->assertMatchesRegularExpression('/\'attempt\'\s*=>\s*\$this->attempts\(\)/', $job);
        $this->assertStringContainsString("config('invoices.gdt.detail_retry_attempts', 4)", $pdfService);
        $this->assertStringContainsString("config('invoices.gdt.detail_retry_backoff_seconds', [5, 10, 20, 40])", $pdfService);
        $this->assertStringContainsString('$response->status() === 429', $pdfService);
        $this->assertStringContainsString('$response->header(\'Retry-After\')', $pdfService);
        $this->assertStringContainsString('$onRateLimitRetry($attempt, $attempts, $delay);', $pdfService);
        $this->assertStringContainsString('sleep($delay);', $pdfService);
    }
}
