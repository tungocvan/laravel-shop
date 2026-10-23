<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceWorkspaceLayoutContractTest extends TestCase
{
    public function test_finance_workspace_has_three_separate_views(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');

        $this->assertStringContainsString("workspaceTab = 'overview'", $component);
        $this->assertStringContainsString("workspaceTab === 'overview'", $view);
        $this->assertStringContainsString("workspaceTab === 'ledger'", $view);
        $this->assertStringContainsString("workspaceTab === 'compose'", $view);
        $this->assertStringContainsString('Tổng quan thu / chi', $view);
        $this->assertStringContainsString('Chi tiết sổ chứng từ', $view);
    }

    public function test_accounting_register_is_filter_first_and_has_totals(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $filter = strpos($view, 'aria-label="Bộ lọc báo cáo"');
        $register = strpos($view, 'aria-label="Sổ chứng từ kế toán"');

        $this->assertNotFalse($filter);
        $this->assertNotFalse($register);
        $this->assertLessThan($register, $filter);
        $this->assertStringContainsString('Tổng cộng (theo bộ lọc)', $view);
        $this->assertStringContainsString('Xuất tất cả Excel', $view);
        $this->assertStringContainsString('Xuất Excel theo bộ lọc', $view);
    }

    public function test_operational_vouchers_are_preserved_but_not_duplicated_on_overview(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');

        $this->assertStringContainsString("workspaceTab === 'compose' || (\$workspaceTab === 'ledger' && \$showVoucherOperations)", $view);
        $this->assertStringContainsString('wire:click="post(', $view);
        $this->assertStringContainsString('wire:click="reverse(', $view);
        $this->assertStringContainsString('showVoucherOperations', $component);
        $this->assertStringContainsString('showHistory', $view);
    }
}
