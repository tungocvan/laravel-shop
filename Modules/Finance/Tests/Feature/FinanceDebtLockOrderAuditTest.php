<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtLockOrderAudit;
use Tests\TestCase;

final class FinanceDebtLockOrderAuditTest extends TestCase
{
    public function test_declared_settlement_order_is_diagnostic_only(): void
    {
        $result = (new FinanceDebtLockOrderAudit)->inspect(FinanceDebtLockOrderAudit::SETTLEMENT);
        self::assertFalse($result['eligible']);
        self::assertSame('sequence_only_not_concurrency_proof', $result['reason_code']);
    }

    public function test_inverted_source_allocation_order_is_rejected(): void
    {
        $result = (new FinanceDebtLockOrderAudit)->inspect(['voucher', 'source', 'allocation']);
        self::assertSame('inverted_lock_order', $result['reason_code']);
    }

    public function test_duplicate_lock_resource_is_rejected(): void
    {
        $result = (new FinanceDebtLockOrderAudit)->inspect(['voucher', 'voucher']);
        self::assertSame('invalid_or_duplicate_lock_resource', $result['reason_code']);
    }

    public function test_posting_cross_service_conflict_is_explicit(): void
    {
        $result = (new FinanceDebtLockOrderAudit)->crossServiceAudit();
        self::assertFalse($result['eligible']);
        self::assertSame('partial_lock_order_alignment_not_concurrency_proof', $result['reason_code']);
        self::assertTrue($result['requires_real_database_contention_tests']);
    }

    public function test_aligned_allocation_source_order_is_still_diagnostic(): void
    {
        $result = (new FinanceDebtLockOrderAudit)->inspect(['voucher', 'allocation', 'source']);
        self::assertSame('sequence_only_not_concurrency_proof', $result['reason_code']);
        self::assertFalse($result['eligible']);
    }

    public function test_no_audit_result_can_activate_settlement(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtLockOrderAudit)->assertSafeForSettlement();
    }
}
