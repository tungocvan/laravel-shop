<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceWorkspaceContractTest extends TestCase
{
    public function test_admin_route_requires_auth_and_finance_view_permission(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $this->assertStringContainsString("'auth:admin'", $routes);
        $this->assertStringContainsString("'permission:finance.view,admin'", $routes);
    }

    public function test_workspace_actions_enforce_granular_permissions(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        foreach (['finance.view', 'finance.account.manage', 'finance.voucher.create', 'finance.voucher.post', 'finance.voucher.reverse'] as $permission) {
            $this->assertStringContainsString("can('".$permission."')", $component);
        }
    }

    public function test_workspace_uses_existing_finance_services(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $this->assertStringContainsString('FinanceWorkflowService::class', $component);
        $this->assertStringContainsString('VoucherPostingService::class', $component);
        $this->assertStringContainsString("where('status', 'posted')", $component);
    }
}
