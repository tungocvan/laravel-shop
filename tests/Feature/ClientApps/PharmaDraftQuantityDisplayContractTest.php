<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaDraftQuantityDisplayContractTest extends TestCase
{
    public function test_draft_quantity_and_lot_warning_have_consistent_units(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));

        $this->assertStringContainsString('$formatQty($current->quantity)', $view);
        $this->assertStringContainsString('$formatQty($lot->quantity_on_hand)', $view);
        $this->assertStringContainsString('data-stock="{{ (float) $lot->quantity_on_hand }}"', $view);
        $this->assertStringContainsString('data-stock-warning', $view);
        $this->assertStringContainsString('refreshStockWarnings', $view);
        $this->assertStringContainsString('Math.max(0,requested-available)', $view);
        $this->assertStringContainsString("maximumFractionDigits:3", $view);
    }
}
