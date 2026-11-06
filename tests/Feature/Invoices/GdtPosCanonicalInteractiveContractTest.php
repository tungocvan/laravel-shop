<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\TestCase;

class GdtPosCanonicalInteractiveContractTest extends TestCase
{
    public function test_canonical_pos_requires_separate_page_two_captcha_and_verified_rows(): void
    {
        $service = file_get_contents(__DIR__.'/../../../Modules/Invoices/Services/GdtInvoiceService.php');
        $component = file_get_contents(__DIR__.'/../../../Modules/Invoices/Livewire/SearchHoadon.php');
        $view = file_get_contents(__DIR__.'/../../../Modules/Invoices/resources/views/livewire/search-hoadon.blade.php');

        $this->assertStringContainsString('processVerifiedPosRows(', $service);
        $this->assertStringContainsString('resumePosCanonicalRows(', $service);
        $this->assertStringContainsString('count($rows) !== $expectedTotal', $service);
        $this->assertStringContainsString('isset($ids[(string) $id])', $service);
        $this->assertStringContainsString('openPosCaptcha(\'sync_page2\')', $component);
        $this->assertStringContainsString('posSyncContinuationId', $component);
        $this->assertStringContainsString('processVerifiedPosRows(', $component);
        $this->assertStringContainsString('Tiếp tục đồng bộ trang 2', $view);
    }
}
