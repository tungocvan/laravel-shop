<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementIntegrity;
use Tests\TestCase;

final class FinanceDebtSettlementIntegrityTest extends TestCase
{
    private function inspect(string $principal = '100.00', string $settled = '20.00', string $requested = '30.00', string $key = 'settlement-unique-001', bool $used = false): array
    {
        return (new FinanceDebtSettlementIntegrity)->inspect($principal, $settled, $requested, $key, $used);
    }

    public function test_valid_amounts_are_only_structural_candidates(): void
    {
        $result = $this->inspect();
        self::assertTrue($result['valid']);
        self::assertSame('requires_atomic_idempotency_and_authoritative_settlement_mapping', $result['reason']);
    }

    public function test_duplicate_key_is_rejected(): void
    {
        self::assertSame('duplicate_idempotency_key', $this->inspect(used: true)['reason']);
    }

    public function test_invalid_key_is_rejected(): void
    {
        self::assertSame('invalid_idempotency_key', $this->inspect(key: 'bad key')['reason']);
    }

    public function test_overpayment_is_rejected(): void
    {
        self::assertSame('payment_exceeds_remaining_principal', $this->inspect(requested: '80.01')['reason']);
    }

    public function test_existing_overpayment_is_rejected(): void
    {
        self::assertSame('existing_settlement_exceeds_principal', $this->inspect(settled: '100.01')['reason']);
    }

    public function test_zero_payment_is_rejected(): void
    {
        self::assertSame('non_positive_principal_or_payment', $this->inspect(requested: '0.00')['reason']);
    }

    public function test_malformed_decimal_is_rejected(): void
    {
        self::assertSame('invalid_amount', $this->inspect(requested: '10.001')['reason']);
    }

    public function test_exact_remaining_amount_is_structurally_valid(): void
    {
        self::assertTrue($this->inspect(requested: '80.00')['valid']);
    }

    public function test_execution_is_blocked_even_for_valid_preflight(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementIntegrity)->assertCanExecute('100.00', '20.00', '30.00', 'settlement-unique-001');
    }
}
