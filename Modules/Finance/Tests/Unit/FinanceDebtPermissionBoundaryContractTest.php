<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPermissionBoundaryContractTest extends TestCase
{
    public function test_mutation_actions_reauthorize_server_side(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $manifest = file_get_contents($root.'/Modules/Finance/config/module.php');
        $routes = file_get_contents($root.'/Modules/Finance/routes/web.php');

        self::assertStringContainsString("'finance.debt.manage'", $manifest);
        self::assertStringContainsString("can('finance.debt.manage')", $component);
        self::assertStringContainsString("can('finance.view')", $component);
        self::assertStringContainsString("->middleware('permission:finance.view,admin')", $routes);

        foreach (['openCreate', 'choosePartner', 'create', 'confirm', 'adjust', 'searchPartners'] as $method) {
            self::assertMatchesRegularExpression(
                '/public function '.preg_quote($method, '/').'\\([^)]*\\): void\\s*\\{\\s*(?:\\$actor\\s*=\\s*)?\\$this->authorizeMutation\\(\\)/s',
                $component,
                'Missing mutation authorization in '.$method
            );
        }

        self::assertMatchesRegularExpression(
            '/public function render\\(\\)\\s*\\{\\s*\\$this->authorizeView\\(\\)/s',
            $component
        );
    }
}
