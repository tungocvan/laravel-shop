<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtReconciliation11GContractTest extends TestCase
{
    public function test_read_only_reconciliation_has_exception_guards_and_explicit_scope(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root.'/Services/FinanceDebtReconciliation11G.php');
        $component = file_get_contents($root.'/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString("->where('d.status', 'confirmed')", $service);
        self::assertStringContainsString('suspect_mappings', $service);
        self::assertStringContainsString('missing_reversals', $service);
        self::assertStringContainsString('missing_reversal_ledgers', $service);
        self::assertStringContainsString('receivable_outstanding_verified', $service);
        self::assertStringContainsString('payable_outstanding_verified', $service);
        self::assertStringContainsString("'review' ELSE 'matched'", $service);
        self::assertStringNotContainsString('->insert(', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
        self::assertStringContainsString("'reconciliation' =>", $component);
        self::assertStringContainsString('Đối soát 11G', $view);
        self::assertStringContainsString('không phải báo cáo số dư đầu kỳ/cuối kỳ', $view);
    }
}
