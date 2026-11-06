<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\TestCase;

class GdtPosSyncFilterInvalidationContractTest extends TestCase
{
    public function test_filter_reset_discards_both_pos_checkpoints_before_clearing_search(): void
    {
        $source = file_get_contents(__DIR__.'/../../../Modules/Invoices/Livewire/SearchHoadon.php');
        $start = strpos($source, 'private function clearSearchPreview(): void');
        $end = strpos($source, 'private function discardPosSyncContinuation(): void', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $body = substr($source, $start, $end - $start);
        $preview = strpos($body, '$this->discardPosContinuation();');
        $sync = strpos($body, '$this->discardPosSyncContinuation();');
        $reset = strpos($body, "\$this->searchState = 'idle';");
        $this->assertNotFalse($preview);
        $this->assertNotFalse($sync);
        $this->assertNotFalse($reset);
        $this->assertTrue($preview < $reset && $sync < $reset);
    }
}
