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
        $this->assertStringContainsString("@page { size: {{ \\$printSettings['paper'] }}", $view);
        $this->assertStringContainsString('show_signatures', $view);
        $this->assertStringContainsString('admin.finance.vouchers.pdf', $view);
        $this->assertStringContainsString("->name('vouchers.pdf')", $routes);
        $this->assertStringContainsString("->name('print.settings.update')", $routes);
        $this->assertStringContainsString('permission:finance.account.manage,admin', $routes);
        $this->assertStringContainsString('BẢN NHÁP', $view);
        $this->assertStringContainsString('ĐÃ HOÀN TÁC', $view);
        $this->assertStringContainsString('window.print()', $view);
        $this->assertStringContainsString('admin.finance.vouchers.print', $ledger);
    }
}
