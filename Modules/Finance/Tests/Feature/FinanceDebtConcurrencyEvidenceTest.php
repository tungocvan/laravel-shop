<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtConcurrencyEvidence;
use Tests\TestCase;

final class FinanceDebtConcurrencyEvidenceTest extends TestCase
{
    private function completeEvidence(): array
    {
        return [
            'driver' => 'mysql',
            'database' => 'finance_test',
            'independent_connections' => true,
            'checks' => [
                'row_lock' => true,
                'idempotency_unique' => true,
                'reversal_unique' => true,
                'rollback' => true,
            ],
        ];
    }

    public function test_missing_evidence_is_not_run_and_settlement_is_disabled(): void
    {
        $result = (new FinanceDebtConcurrencyEvidence)->inspect([]);
        self::assertSame('NOT_RUN_OR_INCOMPLETE', $result['status']);
        self::assertFalse($result['settlement_eligible']);
    }

    public function test_partial_and_non_test_database_evidence_is_rejected(): void
    {
        $service = new FinanceDebtConcurrencyEvidence;
        $missing = $this->completeEvidence();
        $missing['checks']['rollback'] = false;
        self::assertSame('NOT_RUN_OR_INCOMPLETE', $service->inspect($missing)['status']);
        $unsafe = $this->completeEvidence();
        $unsafe['database'] = 'finance';
        self::assertSame('NOT_RUN_OR_INCOMPLETE', $service->inspect($unsafe)['status']);
    }

    public function test_complete_self_report_is_not_execution_authority_or_verified_pass(): void
    {
        $service = new FinanceDebtConcurrencyEvidence;
        $result = $service->inspect($this->completeEvidence());
        self::assertSame('REPORTED_PASS_UNVERIFIED', $result['status']);
        self::assertFalse($result['settlement_eligible']);
        self::assertSame('finance_test', $result['database']);
        self::assertArrayNotHasKey('password', $result);
        self::assertArrayNotHasKey('dsn', $result);
    }

    public function test_even_complete_evidence_cannot_activate_settlement(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtConcurrencyEvidence)->assertCanSettle($this->completeEvidence());
    }
}
