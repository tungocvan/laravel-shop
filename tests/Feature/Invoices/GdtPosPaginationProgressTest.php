<?php

namespace Tests\Feature\Invoices;

use InvalidArgumentException;
use Modules\Invoices\Services\GdtPosPaginationProgress;
use PHPUnit\Framework\TestCase;

class GdtPosPaginationProgressTest extends TestCase
{
    public function test_seventeen_invoices_require_user_driven_second_page(): void
    {
        $progress = new GdtPosPaginationProgress(15, 17);

        $this->assertTrue($progress->needsNextPage());
        $this->assertFalse($progress->isComplete());
        $this->assertSame(2, $progress->nextPageNumber());
    }

    public function test_complete_and_empty_results_require_no_next_page(): void
    {
        foreach ([[17, 17], [0, 0]] as [$received, $total]) {
            $progress = new GdtPosPaginationProgress($received, $total);
            $this->assertTrue($progress->isComplete());
            $this->assertNull($progress->nextPageNumber());
        }
    }

    public function test_partial_or_invalid_progress_cannot_claim_completion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GdtPosPaginationProgress(18, 17);
    }

    public function test_page_size_must_match_observed_gdt_pos_contract(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GdtPosPaginationProgress(15, 17, 50);
    }
}
