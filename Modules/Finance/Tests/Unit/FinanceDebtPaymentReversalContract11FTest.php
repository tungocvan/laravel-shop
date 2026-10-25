<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPaymentReversalContract11FTest extends TestCase
{
    public function test_reversal_key_meets_registry_contract_and_payment_rows_include_voucher_id(): void
    {
        $root = dirname(__DIR__, 4).'/Modules/Finance';
        $reversal = file_get_contents($root.'/Services/FinanceDebtPaymentReversalService.php');
        $reader = file_get_contents($root.'/Services/FinanceDebtPaymentEvidenceReader.php');
        $view = file_get_contents($root.'/resources/views/livewire/debt-workspace.blade.php');

        self::assertMatchesRegularExpression('/^[A-Za-z0-9][A-Za-z0-9:_-]{7,127}$/D', 'reverse-1-1');
        self::assertStringContainsString("'reverse-'.\$debtId.'-'.\$voucherId", $reversal);
        self::assertStringContainsString("'voucher_id' => (int) \$m->voucher_id", $reader);
        self::assertStringContainsString("reversePayment({{ \$row['voucher_id'] }})", $view);
    }
}
