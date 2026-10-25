<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPeriodReport11G2ContractTest extends TestCase
{
    public function test_period_report_is_read_only_dated_and_uses_decimal_sql(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root.'/Services/FinanceDebtPeriodReport11G2.php');
        $component = file_get_contents($root.'/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString('recognized_on', $service);
        self::assertStringContainsString('effective_on', $service);
        self::assertStringContainsString('effective_at', $service);
        self::assertStringContainsString('opening_balance', $service);
        self::assertStringContainsString('principal_movement', $service);
        self::assertStringContainsString('payment_movement', $service);
        self::assertStringContainsString('closing_balance', $service);
        self::assertStringContainsString('evidence_gaps', $service);
        self::assertStringContainsString('paginate(15', $service);
        self::assertStringNotContainsString('->insert(', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
        self::assertStringNotContainsString('(float)', $service);
        self::assertStringContainsString("'periodReport' =>", $component);
        self::assertStringContainsString("setTab('period')", $view);
        self::assertStringContainsString('không phải sổ kế toán', $view);
        self::assertStringNotContainsString("$"+"periodReport['rows']->links()", $view);
        self::assertStringContainsString("gotoPage(1, 'periodPage')", $view);
        self::assertStringContainsString("previousPage('periodPage')", $view);
        self::assertStringContainsString("nextPage('periodPage')", $view);
        self::assertStringContainsString('Phân trang báo cáo công nợ theo kỳ', $view);
        self::assertStringContainsString('Vuốt ngang để xem các cột còn lại.', $view);
    }
}
