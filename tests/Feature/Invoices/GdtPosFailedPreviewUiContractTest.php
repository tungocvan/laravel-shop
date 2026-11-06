<?php

namespace Tests\Feature\Invoices;

use Tests\TestCase;

class GdtPosFailedPreviewUiContractTest extends TestCase
{
    public function test_failed_pos_preview_offers_fresh_captcha_retry_without_unlocking_canonical(): void
    {
        $blade = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));

        $this->assertStringContainsString("$invoiceSource === 'pos' && $searchState === 'failed'", $blade);
        $this->assertStringContainsString("wire:click=\"openPosCaptcha('search')\"", $blade);
        $this->assertStringContainsString('Dữ liệu một phần chưa được xác nhận', $blade);
        $this->assertStringContainsString("searchState !== 'completed'", $blade);
        $this->assertStringContainsString('Đồng bộ canonical', $blade);
    }
}
