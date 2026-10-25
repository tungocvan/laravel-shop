<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementTransactionProtocol;
use Tests\TestCase;

final class FinanceDebtSettlementTransactionProtocolTest extends TestCase
{
    public function test_post_plan_remains_disabled_and_requires_atomic_idempotency(): void
    {
        $plan = (new FinanceDebtSettlementTransactionProtocol)->plan(1, 2, 'unique-post-001');
        self::assertFalse($plan['ready']);
        self::assertSame('debt_id:operation:idempotency_key', $plan['unique_scope']);
        self::assertContains('reserve_unique_idempotency_key', $plan['atomic_steps']);
        self::assertContains('append_settlement_once', $plan['atomic_steps']);
        self::assertSame('finance_debt_obligations', $plan['lock_order'][0]);
    }

    public function test_reversal_requires_linked_append_only_reversal(): void
    {
        $plan = (new FinanceDebtSettlementTransactionProtocol)->plan(1, 2, 'unique-reverse-001', 'reverse');
        self::assertFalse($plan['ready']);
        self::assertContains('append_linked_reversal_once', $plan['atomic_steps']);
        self::assertNotContains('append_settlement_once', $plan['atomic_steps']);
    }

    public function test_invalid_debt_id_fails_closed(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->plan(0, 2, 'unique-post-001');
    }

    public function test_invalid_voucher_id_fails_closed(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->plan(1, -1, 'unique-post-001');
    }

    public function test_invalid_key_fails_closed(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->plan(1, 2, 'bad key');
    }

    public function test_unknown_operation_fails_closed(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->plan(1, 2, 'unique-post-001', 'delete');
    }

    public function test_post_execution_is_disabled(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->assertCanExecute(1, 2, 'unique-post-001');
    }

    public function test_reversal_execution_is_disabled(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementTransactionProtocol)->assertCanExecute(1, 2, 'unique-reverse-001', 'reverse');
    }
}
