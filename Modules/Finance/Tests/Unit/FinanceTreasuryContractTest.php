<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceTreasuryContractTest extends TestCase
{
    public function test_transfers_are_separate_from_business_receipts_and_payments(): void
    {
        $base = dirname(__DIR__, 2);
        $service = file_get_contents($base.'/Services/FinanceTreasuryService.php');
        $migration = file_get_contents($base.'/database/migrations/2026_10_23_000006_create_finance_internal_transfers.php');
        $this->assertStringContainsString("finance_internal_transfers", $migration);
        $this->assertStringContainsString("opening_balance", $migration);
        $this->assertStringContainsString("finance_ledger_entries", $service);
        $this->assertStringContainsString("COALESCE(outgoing.total, 0)", $service);
        $this->assertStringContainsString("COALESCE(incoming.total, 0)", $service);
        $this->assertStringContainsString("Insufficient available account balance.", $service);
        $this->assertStringContainsString("->lockForUpdate()", $service);
    }

    public function test_treasury_ui_has_role_protected_actions(): void
    {
        $base = dirname(__DIR__, 2);
        $component = file_get_contents($base.'/Livewire/Workspace.php');
        $view = file_get_contents($base.'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString("can('finance.account.manage')", $component);
        $this->assertStringContainsString("can('finance.voucher.post')", $component);
        $this->assertStringContainsString("saveInternalTransfer", $view);
        $this->assertStringContainsString("Quỹ & Ngân hàng", $view);
    }
}
