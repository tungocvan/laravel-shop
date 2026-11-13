<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaOrdersTotalColumnContractTest extends TestCase
{
    #[Test]
    public function desktop_orders_table_autosizes_and_keeps_currency_on_one_line(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php'));

        $this->assertStringContainsString('table-auto text-left text-sm', $view);
        $this->assertStringContainsString('w-px whitespace-nowrap px-5 py-4 text-right">Tổng tiền', $view);
        $this->assertStringContainsString('whitespace-nowrap px-5 py-4 text-right font-black', $view);
        $this->assertStringContainsString('$money($issue->total_value ?? 0)', $view);
    }
}
