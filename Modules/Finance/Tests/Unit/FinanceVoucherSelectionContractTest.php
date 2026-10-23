<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceVoucherSelectionContractTest extends TestCase
{
    public function test_selection_is_validated_and_scoped_to_ledger_query(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceReportService.php');
        $this->assertStringContainsString("'ids' => 'nullable|array|max:500'", $service);
        $this->assertStringContainsString("'ids.*' => 'required|integer|min:1|distinct'", $service);
        $this->assertStringContainsString("whereIn('v.id', \$filters['ids'])", $service);
        $this->assertStringContainsString("where('ledger.net_amount', '<>', 0)", $service);
    }

    public function test_export_selection_and_filter_buttons_are_distinct(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString('name="ids[]"', $view);
        $this->assertStringContainsString('Chọn tất cả chứng từ đang hiển thị', $view);
        $this->assertStringContainsString('Xuất đã chọn', $view);
        $this->assertStringContainsString('Xuất Excel theo bộ lọc', $view);
        $this->assertStringContainsString('Xóa nháp', $view);
    }
}
