<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPaymentEvidenceContractTest extends TestCase
{
    public function test_reconciliation_does_not_claim_a_payment_or_mutate_vouchers(): void
    {
        $root = dirname(__DIR__, 4);
        $reader = file_get_contents($root.'/Modules/Finance/Services/FinanceDebtPaymentEvidenceReader.php');
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');

        self::assertStringContainsString("'verified_paid' => null", $reader);
        self::assertStringContainsString("'verified_outstanding' => null", $reader);
        self::assertStringContainsString('Chưa xác minh thanh toán', $reader);
        self::assertStringContainsString('Đối chiếu lịch sử thanh toán', $view);
        self::assertStringContainsString('disabled class=', $view);
        self::assertStringNotContainsString('DB::table(\'finance_vouchers\')->insert', $reader);
        self::assertStringNotContainsString('DB::table(\'finance_debt_settlement_mappings\')->insert', $reader);
    }
}
