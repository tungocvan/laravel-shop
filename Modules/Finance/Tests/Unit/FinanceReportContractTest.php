<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceReportContractTest extends TestCase
{
    public function test_report_is_ledger_backed_and_reversals_are_excluded(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceReportService.php');
        $this->assertStringContainsString('finance_ledger_entries', $service);
        $this->assertStringContainsString("where('ledger.net_amount', '<>', 0)", $service);
        $this->assertStringContainsString("whereIn('v.status', ['posted', 'reversed'])", $service);
        $this->assertStringContainsString('validateFilters', $service);
    }

    public function test_export_has_separate_receipt_and_payment_columns(): void
    {
        $export = file_get_contents(dirname(__DIR__, 2).'/Exports/FinanceVoucherRegisterExport.php');
        $this->assertStringContainsString('Thu (VND)', $export);
        $this->assertStringContainsString('Chi (VND)', $export);
        $this->assertStringContainsString('WithColumnFormatting', $export);
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $this->assertStringContainsString('reports.vouchers.excel', $routes);
        $this->assertStringContainsString('permission:finance.view,admin', $routes);
    }

    public function test_workspace_has_dashboard_and_both_excel_modes(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString('Xuất tất cả Excel', $view);
        $this->assertStringContainsString('Xuất Excel theo bộ lọc', $view);
        $this->assertStringContainsString('Thu trừ chi', $view);
        $this->assertStringContainsString('reportRows', $view);
    }
}
