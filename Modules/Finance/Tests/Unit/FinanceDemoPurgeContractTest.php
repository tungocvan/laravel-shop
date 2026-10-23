<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDemoPurgeContractTest extends TestCase
{
    public function test_demo_purge_requires_balanced_reversal_and_denies_production(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceWorkflowService.php');
        $this->assertStringContainsString("app()->environment('production')", $service);
        $this->assertStringContainsString("['posting', 'reversal']", $service);
        $this->assertStringContainsString("['draft', 'cancelled', 'reversed']", $service);
        $this->assertStringContainsString("signedCents !== 0", $service);
        $this->assertStringContainsString("Finance demo voucher permanently deleted", $service);
    }

    public function test_permission_and_role_required_by_livewire_and_ui(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $manifest = file_get_contents(dirname(__DIR__, 2).'/config/module.php');
        $this->assertStringContainsString("finance.voucher.force-delete", $manifest);
        $this->assertStringContainsString("can('finance.voucher.force-delete')", $component);
        $this->assertStringContainsString('hasAnyRole(', $component);
        $this->assertStringContainsString("permanentlyDeleteDemoVoucher", $view);
        $this->assertStringContainsString('Xóa chứng từ', $view);
    }
}
