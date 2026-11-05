<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Livewire\SearchHoadon;
use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtTabSearchContractTest extends TestCase
{
    #[Test]
    public function search_tabs_are_explicit_and_canonical_sync_requires_a_matching_preview(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $component = file_get_contents((new \ReflectionClass(SearchHoadon::class))->getFileName());

        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString('Hóa đơn thông thường', $view);
        $this->assertStringContainsString('Hóa đơn máy tính tiền', $view);
        $this->assertStringContainsString('wire:click="searchInvoices"', $view);
        $this->assertStringContainsString('wire:click="run"', $view);
        $this->assertStringContainsString("public string \$invoiceSource = 'regular';", $component);
        $this->assertStringContainsString('public function searchInvoices(): void', $component);
        $this->assertStringContainsString('searchedFingerprint', $component);
        $this->assertStringContainsString('consumePosCaptcha', $component);
    }

    #[Test]
    public function preview_does_not_persist_canonical_data(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $start = strpos($source, 'public function previewSource(');
        $end = strpos($source, 'public function expectedExportPath(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $preview = substr($source, $start, $end - $start);
        $this->assertStringContainsString('fetchInvoicesByMonth(', $preview);
        $this->assertStringNotContainsString('persistInvoices(', $preview);
        $this->assertStringNotContainsString('exportExcel(', $preview);
    }
}
