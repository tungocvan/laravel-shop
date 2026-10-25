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
        $pagination = file_get_contents($root.'/resources/views/livewire/partials/debt-report-pagination.blade.php');
        self::assertStringContainsString("pageName' => 'periodPage'", $view);
        self::assertStringContainsString('Phân trang báo cáo công nợ theo kỳ', $view);
        self::assertStringContainsString('gotoPage(1,', $pagination);
        self::assertStringContainsString('previousPage(', $pagination);
        self::assertStringContainsString('nextPage(', $pagination);
        self::assertStringContainsString('Vuốt ngang để xem các cột còn lại.', $view);
        $periodPanel = explode("    @elseif (\$activeTab === 'reconciliation')", explode("    @elseif (\$activeTab === 'period')", $view, 2)[1], 2)[0];
        self::assertStringContainsString('Báo cáo theo kỳ · Chỉ đọc', $periodPanel);
        self::assertStringContainsString('bg-white p-4 shadow-sm', $periodPanel);
        self::assertStringContainsString('divide-y divide-slate-100', $periodPanel);
        self::assertStringContainsString('hover:bg-slate-50/80', $periodPanel);
        self::assertStringContainsString('tabular-nums', $periodPanel);
        self::assertStringContainsString('aria-label="Bảng biến động công nợ"', $periodPanel);
        self::assertStringContainsString('Cần đối soát', $periodPanel);
        self::assertStringContainsString("pageName' => 'periodPage'", $periodPanel);
    }
}
