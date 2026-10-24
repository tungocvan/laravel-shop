<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtCurrencyUiContractTest extends TestCase
{
    public function test_currency_input_and_list_display_are_separated_from_persisted_amount(): void
    {
        $root = dirname(__DIR__, 4);
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        self::assertStringContainsString("wire.set('amount'", $view);
        self::assertStringContainsString('inputmode="numeric"', $view);
        self::assertStringContainsString("DebtWorkspace::formatVnd((string) \$debt->original_amount)", $view);
        self::assertStringContainsString('partner-picker', $view);
    }
}
