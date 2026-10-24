<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class FinanceDebtWorkspaceContractTest extends TestCase
{
    public function test_debt_workspace_is_permission_gated_and_preserves_finance_route(): void
    {
        $routes = file_get_contents(__DIR__.'/../../Modules/Finance/routes/web.php');

        self::assertStringContainsString("'/debts/{section?}'", $routes);
        self::assertStringContainsString("->whereIn('section'", $routes);
        self::assertStringContainsString("->middleware('permission:finance.view,admin')", $routes);
        self::assertStringContainsString("->name('debts.index')", $routes);
        self::assertStringContainsString("->name('index')", $routes);
    }

    public function test_debt_sidebar_is_responsive_and_has_return_link_without_mutations(): void
    {
        $view = file_get_contents(__DIR__.'/../../Modules/Finance/resources/views/debts/workspace.blade.php');

        foreach (['dashboard', 'partners', 'documents', 'ledger', 'summary', 'aging', 'settings'] as $section) {
            self::assertStringContainsString("'".$section."' =>", $view);
        }
        self::assertStringContainsString("route('admin.finance.index')", $view);
        self::assertStringContainsString('lg:hidden', $view);
        self::assertStringContainsString('Chưa có dữ liệu công nợ', $view);
        self::assertStringContainsString('<livewire:finance.debt-workspace />', $view);
        self::assertStringContainsString("$"+"section === 'documents'", $view);
    }
}
