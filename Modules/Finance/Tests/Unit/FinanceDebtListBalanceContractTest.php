<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtListBalanceContractTest extends TestCase
{
    public function test_list_projects_one_exact_aggregate_per_paginated_row_without_n_plus_one(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString("->selectRaw('(finance_debt_obligations.original_amount + (SELECT COALESCE(SUM(signed_amount), 0) FROM finance_debt_events WHERE obligation_id = finance_debt_obligations.id)) AS adjusted_balance')", $component);
        self::assertStringContainsString('->paginate(10)', $component);
        self::assertStringContainsString('Số dư sau điều chỉnh</th>', $view);
        self::assertStringContainsString('formatVnd((string) $debt->adjusted_balance)', $view);
        self::assertStringContainsString('colspan="8"', $view);
    }
}
