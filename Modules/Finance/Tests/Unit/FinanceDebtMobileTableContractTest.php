<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtMobileTableContractTest extends TestCase
{
    public function test_debt_table_scrolls_horizontally_without_clamping_column_widths(): void
    {
        $root = dirname(__DIR__, 4);
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString('max-w-full overflow-x-auto overscroll-x-contain', $view);
        self::assertStringContainsString('min-w-[960px]', $view);
        self::assertStringContainsString('aria-label="Bảng danh sách công nợ, vuốt ngang để xem các cột"', $view);
        self::assertStringContainsString('tabindex="0"', $view);
        self::assertStringContainsString('Vuốt ngang trong bảng để xem thêm cột.', $view);
        self::assertStringContainsString('whitespace-nowrap', $view);
        self::assertStringNotContainsString('overflow-y-hidden', $view);
        self::assertStringNotContainsString('touch-none', $view);
    }
}
