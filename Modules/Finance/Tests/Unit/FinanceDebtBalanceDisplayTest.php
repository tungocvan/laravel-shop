<?php

namespace Modules\Finance\Tests\Unit;

use Modules\Finance\Livewire\DebtWorkspace;
use PHPUnit\Framework\TestCase;

final class FinanceDebtBalanceDisplayTest extends TestCase
{
    public function test_vnd_formatter_preserves_sign_and_decimal_precision(): void
    {
        self::assertSame('−500.000 ₫', DebtWorkspace::formatVnd('-500000.00'));
        self::assertSame('5.000.000 ₫', DebtWorkspace::formatVnd('5000000.00'));
        self::assertSame('4.500.000 ₫', DebtWorkspace::formatVnd('4500000.00'));
        self::assertSame('1.234,5 ₫', DebtWorkspace::formatVnd('1234.50'));
    }

    public function test_summary_uses_all_events_and_keeps_cash_settlement_out_of_scope(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString('COALESCE(SUM(signed_amount), 0)', $component);
        self::assertStringContainsString("'adjustmentTotal' =>", $component);
        self::assertStringContainsString("'currentBalance' =>", $component);
        self::assertStringContainsString('Số dư sau điều chỉnh', $view);
        self::assertStringContainsString('chưa trừ thanh toán hoặc đối trừ', $view);
        self::assertStringContainsString('formatVnd((string) $event->signed_amount)', $view);
    }
}
