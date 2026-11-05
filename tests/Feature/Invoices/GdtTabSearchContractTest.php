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
        $this->assertStringContainsString("'searchInvoices'", $view);
        $this->assertStringContainsString("'run'", $view);
        $this->assertStringContainsString("public string \$invoiceSource = 'regular';", $component);
        $this->assertStringContainsString('public function searchInvoices(): void', $component);
        $this->assertStringContainsString('searchedFingerprint', $component);
        $this->assertStringContainsString('consumePosCaptcha', $component);
        $this->assertStringContainsString("$"."submittedPosCaptchaAnswer = $"."this->invoiceSource === 'pos' ? $"."this->posCaptchaAnswer : '';", $component);
        $this->assertStringContainsString("consumePosCaptcha((int) auth('admin')->id(), $"."submittedPosCaptchaAnswer)", $component);
        $this->assertStringContainsString('public function openPosCaptcha(', $component);
        $this->assertStringContainsString('public function submitPosCaptcha(): void', $component);
        $this->assertStringContainsString('public function openPosSyncCaptcha(): void', $component);
        $this->assertStringContainsString('wire:click="submitPosCaptcha"', $view);
        $this->assertStringContainsString('role="dialog"', $view);
        $this->assertStringContainsString("'openPosSyncCaptcha'", $view);
        $this->assertStringContainsString("(int) (\$this->searchPreview['count'] ?? 0) <= 0", $component);
        $this->assertStringContainsString("(int) (\$searchPreview['count'] ?? 0) <= 0", $view);
        $this->assertStringContainsString('Không tìm thấy hóa đơn', $view);
    }

    #[Test]
    public function default_purchase_direction_matches_first_select_option_and_backend_mapping(): void
    {
        $component = new SearchHoadon;
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));

        $this->assertTrue((bool) $component->vatIn);
        $this->assertSame('purchase', $component->vatIn ? 'purchase' : 'sold');
        $this->assertMatchesRegularExpression(
            '/<select[^>]*wire:model\\.live="vatIn"[^>]*>\\s*<option value="1">Mua vào<\\/option>\\s*<option value="0">Bán ra<\\/option>/',
            $view,
        );
        $component->vatIn = false;
        $this->assertSame('sold', $component->vatIn ? 'purchase' : 'sold');
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
