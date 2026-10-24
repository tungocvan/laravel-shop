<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtUiContractTest extends TestCase
{
    public function test_debt_workspace_reuses_service_and_checks_permissions(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        $shell = file_get_contents($root.'/Modules/Finance/resources/views/debts/workspace.blade.php');

        self::assertStringContainsString("can('finance.view')", $component);
        self::assertStringContainsString("can('finance.debt.manage')", $component);
        self::assertStringContainsString('FinanceDebtService $service', $component);
        self::assertStringContainsString('->paginate(10)', $component);
        self::assertStringContainsString('->where(\'direction\'', $component);
        self::assertStringContainsString('wire:confirm=', $view);
        self::assertStringContainsString('wire:loading.attr="disabled"', $view);
        self::assertStringContainsString('wire:submit="create"', $view);
        self::assertStringContainsString('wire:submit="adjust"', $view);
        self::assertStringContainsString('<livewire:finance.debt-workspace />', $shell);
    }
}
