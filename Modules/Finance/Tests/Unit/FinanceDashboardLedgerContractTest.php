<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDashboardLedgerContractTest extends TestCase
{
    public function test_account_ledger_includes_vouchers_and_both_sides_of_transfers(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceTreasuryService.php');
        $this->assertStringContainsString('function accountLedger(', $service);
        $this->assertStringContainsString("finance_ledger_entries as e", $service);
        $this->assertStringContainsString("t.from_account_id", $service);
        $this->assertStringContainsString("t.to_account_id", $service);
        $this->assertStringContainsString('running_balance', $service);
        $this->assertStringContainsString('opening_balance', $service);
    }

    public function test_dashboard_shows_currency_separated_totals_and_account_ledger(): void
    {
        $base = dirname(__DIR__, 2);
        $component = file_get_contents($base.'/Livewire/Workspace.php');
        $view = file_get_contents($base.'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString("groupBy('currency')", $component);
        $this->assertStringContainsString("function viewAccountLedger(", $component);
        $this->assertStringContainsString('Tiền mặt & tiền gửi hiện có', $view);
        $this->assertStringContainsString('Sổ chi tiết tài khoản', $view);
        $this->assertStringContainsString('Cảnh báo: số dư âm', $view);
    }
}
