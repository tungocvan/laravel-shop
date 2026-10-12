<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaProductBackfillContractTest extends TestCase
{
    #[Test]
    public function backfill_defaults_to_read_only_and_requires_explicit_apply(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Console/Commands/BackfillPharmaProducts.php'));
        $this->assertStringContainsString('pharma:products-backfill', $source);
        $this->assertStringContainsString('{--apply', $source);
        $this->assertStringContainsString("limit=100", $source);
        $this->assertStringContainsString("if (\$apply)", $source);
        $this->assertStringContainsString('DRY-RUN', $source);
    }

    #[Test]
    public function queue_job_has_retry_and_failure_reporting(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Jobs/SyncMedicineProducts.php'));
        $this->assertStringContainsString('public int $tries = 3;', $source);
        $this->assertStringContainsString('public array $backoff = [10, 60, 300];', $source);
        $this->assertStringContainsString('function failed(?Throwable $exception)', $source);
        $this->assertStringContainsString("'sync_status' => 'error'", $source);
    }
}
