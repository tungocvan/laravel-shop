<?php

namespace Modules\Finance\Tests\Unit;

use Modules\Finance\Support\VietnameseMoney;
use PHPUnit\Framework\TestCase;

final class FinanceVoucherPrintTest extends TestCase
{
    public function test_vietnamese_amount_in_words(): void
    {
        $this->assertSame('Không đồng chẵn.', VietnameseMoney::spell('0.00'));
        $this->assertSame('Mười triệu đồng chẵn.', VietnameseMoney::spell('10000000.00'));
        $this->assertSame('Một triệu không trăm lẻ năm đồng chẵn.', VietnameseMoney::spell('1000005.00'));
        $this->assertSame('Một nghìn hai trăm ba mươi bốn đồng và năm mươi xu.', VietnameseMoney::spell('1234.50'));
    }

    public function test_print_route_is_authenticated_and_read_only(): void
    {
        $base = dirname(__DIR__, 2);
        $routes = file_get_contents($base.'/routes/web.php');
        $controller = file_get_contents($base.'/Http/Controllers/FinanceVoucherPrintController.php');
        $view = file_get_contents($base.'/resources/views/vouchers/print-a5.blade.php');
        $ledger = file_get_contents($base.'/resources/views/livewire/workspace.blade.php');

        $this->assertStringContainsString("'auth:admin'", $routes);
        $this->assertStringContainsString("'permission:finance.view,admin'", $routes);
        $this->assertStringContainsString("->name('vouchers.print')", $routes);
        $this->assertStringContainsString("abort_if(\$document === null, 404)", $controller);
        $this->assertStringNotContainsString('->update(', $controller);
        $this->assertStringNotContainsString('->insert(', $controller);
        $this->assertStringContainsString("@page { size: {{ \$printSettings['paper'] }}", $view);
        $this->assertStringContainsString('show_signatures', $view);
        $this->assertStringContainsString('admin.finance.vouchers.pdf', $view);
        $this->assertStringContainsString("->name('vouchers.pdf')", $routes);
        $this->assertStringContainsString("->name('print.settings.update')", $routes);
        $this->assertStringContainsString('permission:finance.account.manage,admin', $routes);
        $this->assertStringContainsString('BẢN NHÁP', $view);
        $this->assertStringContainsString('ĐÃ HOÀN TÁC', $view);
        $this->assertStringContainsString('window.print()', $view);
        $this->assertStringContainsString("font-family: 'DejaVu Sans'", $view);
        $this->assertStringContainsString('.pdf-export .paper { position: static; width: {{ $contentWidth }}mm; min-height: 0; padding: 0;', $view);
        $this->assertStringContainsString('.signatures > div { display: table-cell;', $view);
        $this->assertStringContainsString('admin.finance.vouchers.print', $ledger);
        $this->assertStringContainsString('.line { display: table; table-layout: fixed;', $view);
        $this->assertStringContainsString('.line .value { display: table-cell;', $view);
        $this->assertStringContainsString('.signatures { display: table;', $view);
        $this->assertStringContainsString('.signature-space { height: 28px;', $view);
        $this->assertStringContainsString('.pdf-export .signatures > div { display: table-cell;', $view);
        $this->assertStringContainsString('<div class="details">', $view);
        $this->assertStringContainsString("printSettings['orientation'] === 'landscape'", $view);
        $this->assertStringContainsString('$paperWidth', $view);
        $this->assertStringContainsString('$paperHeight', $view);
        $this->assertStringContainsString('max-width: {{ $paperWidth }}mm;', $view);
        $this->assertStringContainsString('width: {{ $paperWidth }}mm; min-height: {{ $paperHeight }}mm;', $view);
        $this->assertStringContainsString("'defaultFont' => 'DejaVu Sans'", $controller);
        $this->assertStringContainsString("'isRemoteEnabled' => false", $controller);
        $this->assertStringContainsString('charset=utf-8', $view);
        $this->assertStringContainsString("max(12, (int) \$printSettings['margin_mm'])", $view);
        $this->assertStringContainsString('@page { size: {{ $printSettings[\'paper\'] }} {{ $printSettings[\'orientation\'] }}; margin: 0; }', $view);
        $this->assertStringContainsString('padding: {{ $printMargin }}mm; margin: auto;', $view);
        $this->assertStringContainsString('$contentWidth = $paperWidth - 2 * $printMargin;', $view);
        $this->assertStringContainsString('margin: {{ $printMargin }}mm 0 0 {{ $printMargin }}mm;', $view);
        $this->assertStringContainsString('.amount { border: 0; padding: 0;', $view);
        $this->assertStringContainsString('Bằng chữ: {{ $amountInWords }}', $view);
        $this->assertStringNotContainsString('<strong>{{ $amountInWords }}</strong>', $view);
        $this->assertStringContainsString("font-size: {{ \$printSettings['title_font_size'] }}pt;", $view);
        $this->assertStringContainsString('font-weight: 400;', $view);
    }
}
