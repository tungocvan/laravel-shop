<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtSharedSearchAndMoneyContractTest extends TestCase
{
    public function test_shared_search_keeps_legacy_input_and_adds_select_variant(): void
    {
        $root = dirname(__DIR__, 4);
        $search = file_get_contents($root.'/resources/views/components/search.blade.php');
        $select = file_get_contents($root.'/resources/views/components/search-select.blade.php');
        self::assertStringContainsString("'variant' => 'input'", $search);
        self::assertStringContainsString("components.search-select", $search);
        self::assertStringContainsString("data-search-autocomplete", $search);
        self::assertStringContainsString('x-on:click="open = !open"', $select);
        self::assertStringContainsString('wire:model.live.debounce.350ms=', $select);
        self::assertStringContainsString('wire:click=', $select);
    }

    public function test_debt_defaults_due_date_and_formats_money_in_ui_only(): void
    {
        $root = dirname(__DIR__, 4);
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        self::assertStringContainsString('now()->addDays(30)->toDateString()', $component);
        self::assertStringContainsString("DebtWorkspace::formatVnd((string) \$selected->original_amount)", $view);
        self::assertStringContainsString("wire.set('adjustment'", $view);
        self::assertStringContainsString('inputmode="numeric"', $view);
    }
}
