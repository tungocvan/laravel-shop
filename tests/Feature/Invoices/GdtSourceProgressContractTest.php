<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtSourceProgressContractTest extends TestCase
{
    #[Test]
    public function all_source_job_records_partial_progress_without_swallowing_pos_failure(): void
    {
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString("updateSourceProgress('regular', 'persisted')", $job);
        $this->assertStringContainsString("updateSourceProgress('pos', 'failed')", $job);
        $this->assertStringContainsString("updateStatus('partial'", $job);
        $this->assertStringContainsString("'retry_pending' => \$this->attempts() < \$this->tries", $job);
        $this->assertStringContainsString('throw $exception;', $job);
        $this->assertStringContainsString("'source_progress'", $job);
    }
}
