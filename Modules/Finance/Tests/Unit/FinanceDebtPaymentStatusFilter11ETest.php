<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPaymentStatusFilter11ETest extends TestCase
{
    public function test_payment_filter_uses_posted_owned_settlements_and_resets_pagination(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/DebtWorkspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString("public string \$paymentStatus = '';", $component);
        self::assertStringContainsString('public function updatedPaymentStatus(): void { $this->resetPage(); }', $component);
        self::assertStringContainsString("['paid', 'partial', 'unpaid']", $component);
        self::assertStringContainsString("m.operation = 'post' AND v.status = 'posted'", $component);
        self::assertStringContainsString('JOIN finance_debt_allocation_ownerships o', $component);
        self::assertStringContainsString("->whereRaw(\$paidSql.' > 0 AND '.\$paidSql.' >= '.\$principalSql)", $component);
        self::assertStringContainsString("->whereRaw(\$paidSql.' > 0 AND '.\$paidSql.' < '.\$principalSql)", $component);
        self::assertStringContainsString('wire:model.live="paymentStatus"', $view);
        self::assertStringContainsString('<option value="paid">Đã thanh toán</option>', $view);
    }
}
