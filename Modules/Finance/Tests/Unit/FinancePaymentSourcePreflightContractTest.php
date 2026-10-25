<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinancePaymentSourcePreflightContractTest extends TestCase
{
    public function test_payment_review_rechecks_source_identity_currency_and_direction_without_posting(): void
    {
        $root = dirname(__DIR__, 4);
        $review = file_get_contents($root.'/Modules/Finance/Services/FinanceDebtPaymentReview.php');

        self::assertStringContainsString("where('id', \$link['source_id'])", $review);
        self::assertStringContainsString("\$source->currency !== \$debt->currency", $review);
        self::assertStringContainsString("(string) \$source->original_amount !== (string) \$debt->original_amount", $review);
        self::assertStringContainsString("\$source->direction !== \$debt->direction", $review);
        self::assertStringContainsString("\$invoice->invoice_type", $review);
        self::assertStringContainsString("'eligible' => \$reasons === []", $review);
        self::assertStringNotContainsString("DB::table('finance_vouchers')->insert", $review);
        self::assertStringNotContainsString("DB::table('finance_ledger_entries')->insert", $review);
    }
}
