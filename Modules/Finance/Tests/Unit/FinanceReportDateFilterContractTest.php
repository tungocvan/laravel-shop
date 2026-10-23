<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceReportDateFilterContractTest extends TestCase
{
    public function test_date_filters_apply_only_after_explicit_action(): void
    {
        $base = dirname(__DIR__, 2);
        $component = file_get_contents($base.'/Livewire/Workspace.php');
        $view = file_get_contents($base.'/resources/views/livewire/workspace.blade.php');
        $this->assertStringContainsString('function applyReportDates()', $component);
        $this->assertStringContainsString('startOfMonth()->toDateString()', $component);
        $this->assertStringContainsString('after_or_equal:ledgerDateFrom', $component);
        $this->assertStringContainsString("'from' => \$this->appliedLedgerDateFrom", $component);
        $this->assertStringContainsString("'to' => \$this->appliedLedgerDateTo", $component);
        $this->assertStringContainsString('wire:submit="applyReportDates"', $view);
        $this->assertStringContainsString('wire:model="ledgerDateFrom"', $view);
        $this->assertStringContainsString('wire:model="ledgerDateTo"', $view);
        $this->assertStringNotContainsString('wire:model.live="ledgerDateFrom"', $view);
        $this->assertStringNotContainsString('wire:model.live="ledgerDateTo"', $view);
    }
}
