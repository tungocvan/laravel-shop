<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceVoucherActionContractTest extends TestCase
{
    public function test_print_requires_posted_status_on_server(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/Http/Controllers/FinanceVoucherPrintController.php');
        $this->assertStringContainsString("\$document->status !== 'posted'", $controller);
    }

    public function test_permanent_deletion_is_admin_gated_and_never_deletes_ledger(): void
    {
        $workspace = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceWorkflowService.php');
        $this->assertStringContainsString('hasAnyRole(', $workspace);
        $this->assertStringContainsString('permanentlyDeleteUnposted(', $workspace);
        $this->assertStringContainsString("['draft', 'cancelled']", $service);
        $this->assertStringContainsString("DB::table('finance_ledger_entries')->where('voucher_id', \$voucherId)->exists()", $service);
        $this->assertStringContainsString("DB::table('finance_allocations')->where('voucher_id', \$voucherId)->delete()", $service);
    }

    public function test_action_menu_is_status_aware(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString('Thao tác ▾', $view);
        $this->assertStringContainsString("status === 'posted'", $view);
        $this->assertStringContainsString('Xóa vĩnh viễn (Admin)', $view);
    }
}
