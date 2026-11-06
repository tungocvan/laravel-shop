<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\TestCase;

class GdtPosInteractivePaginationContractTest extends TestCase
{
    public function test_interactive_preview_pauses_before_page_two_and_requires_separate_canonical_captcha(): void
    {
        $service = file_get_contents(__DIR__.'/../../../Modules/Invoices/Services/GdtInvoiceService.php');
        $component = file_get_contents(__DIR__.'/../../../Modules/Invoices/Livewire/SearchHoadon.php');
        $view = file_get_contents(__DIR__.'/../../../Modules/Invoices/resources/views/livewire/search-hoadon.blade.php');

        $this->assertStringContainsString('previewPosFirstPage(', $service);
        $this->assertStringContainsString('resumePosPreview(', $service);
        $this->assertStringContainsString('if ($cashRegister && $stopAfterFirstPosPage && $page === 1', $service);
        $this->assertStringContainsString('GdtPosSecureContinuation::class', $component);
        $this->assertStringContainsString("openPosCaptcha('page2')", $component);
        $this->assertStringContainsString('posMultiPagePreview', $component);
        $this->assertStringContainsString("openPosCaptcha('sync_page2')", $component);
        $this->assertStringContainsString('Tiếp tục trang 2', $view);
    }
}
