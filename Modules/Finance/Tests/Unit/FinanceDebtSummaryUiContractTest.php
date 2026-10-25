<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtSummaryUiContractTest extends TestCase
{
    public function test_summary_reuses_shared_finance_pagination_and_accessible_table(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/livewire/debt-summary.blade.php');
        $component = file_get_contents($root.'/Livewire/DebtSummary.php');
        $pagination = file_get_contents($root.'/resources/views/livewire/partials/debt-report-pagination.blade.php');

        self::assertStringContainsString("pageName' => 'summaryPage'", $view);
        self::assertStringContainsString("paginate(15, ['*'], 'summaryPage')", $component);
        self::assertStringContainsString("resetPage('summaryPage')", $component);
        self::assertStringNotContainsString('$groups->links()', $view);
        self::assertStringContainsString('Phân trang tổng hợp công nợ', $view);
        self::assertStringContainsString('previousPage(', $pagination);
        self::assertStringContainsString('nextPage(', $pagination);
        self::assertStringContainsString('aria-current="page"', $pagination);
        self::assertStringContainsString('Phạm vi số liệu', $view);
        self::assertStringContainsString('không phải phát sinh kế toán trong kỳ', $view);
        self::assertStringContainsString('Vuốt ngang để xem các cột còn lại.', $view);
        self::assertStringContainsString('aria-label="Bảng tổng hợp theo đối tác"', $view);
        self::assertStringContainsString('tabular-nums', $view);
        self::assertStringContainsString('Xem chi tiết', $view);
        self::assertStringContainsString("route('admin.finance.debts.index', ['section' => 'ledger'])", $view);
        self::assertStringContainsString("can('finance.view')", $component);
        self::assertStringNotContainsString('->update(', $component);
        self::assertStringNotContainsString('->delete(', $component);
    }
}
