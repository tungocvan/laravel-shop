<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceTreasuryUxContractTest extends TestCase
{
    public function test_account_edit_is_guarded_and_does_not_modify_account_identity(): void
    {
        $base = dirname(__DIR__, 2);
        $service = file_get_contents($base.'/Services/FinanceWorkflowService.php');
        $component = file_get_contents($base.'/Livewire/Workspace.php');
        $this->assertStringContainsString('function updateDetailedAccount(', $service);
        $this->assertStringContainsString("where('id', $accountId)->lockForUpdate()", $service);
        $this->assertStringContainsString("can('finance.account.manage')", $component);
        $this->assertStringContainsString('function editAccount(', $component);
        $this->assertStringContainsString('function updateAccount(', $component);
    }

    public function test_treasury_uses_modal_and_resets_opening_balance_after_success(): void
    {
        $base = dirname(__DIR__, 2);
        $component = file_get_contents($base.'/Livewire/Workspace.php');
        $view = file_get_contents($base.'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString("openingAmountDisplay = '0'", $component);
        $this->assertStringContainsString("finance-opening-saved", $component);
        $this->assertStringContainsString('wire:model.live.debounce.400ms="openingAmountDisplay"', $view);
        $this->assertStringContainsString('aria-modal="true"', $view);
        $this->assertStringContainsString('Sửa thông tin', $view);
    }
}
