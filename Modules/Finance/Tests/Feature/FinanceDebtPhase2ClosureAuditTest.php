<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Finance\Services\FinanceDebtConcurrencyReadiness;
use Modules\Finance\Services\FinanceDebtSettlementTransactionProtocol;
use Tests\TestCase;

/**
 * CP30 audit regression: current settlement feature must remain fail-closed.
 * This is a contract check, NOT an execution or concurrency proof.
 */
final class FinanceDebtPhase2ClosureAuditTest extends TestCase
{
    public function test_transaction_plan_still_disables_real_settlement(): void
    {
        $plan = (new FinanceDebtSettlementTransactionProtocol)->plan(1, 1, 'cp30-audit-key');
        self::assertFalse($plan['ready']);
        self::assertSame('authoritative_mapping_and_atomic_storage_not_implemented', $plan['reason']);
        self::assertContains('reserve_unique_idempotency_key', $plan['atomic_steps']);
        self::assertContains('commit_all_or_rollback_all', $plan['atomic_steps']);
    }

    public function test_concurrency_evidence_does_not_grant_settlement_authority(): void
    {
        $readiness = new FinanceDebtConcurrencyReadiness;
        self::assertFalse($readiness->inspect()['eligible']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $readiness->assertReadyForSettlement();
    }

    public function test_atomic_gate_retains_unconditional_denial_and_no_reservation_call(): void
    {
        $source = file_get_contents(base_path('Modules/Finance/Services/FinanceDebtAtomicSettlementGate.php'));
        self::assertIsString($source);
        self::assertStringContainsString('Atomic settlement blocked:', $source);
        self::assertStringContainsString('throw ValidationException::withMessages', $source);
        self::assertStringNotContainsString('->reserve(', $source);
        self::assertStringNotContainsString('->insert(', $source);
        self::assertStringNotContainsString('->update(', $source);
        self::assertStringNotContainsString('->delete(', $source);
    }

    public function test_closure_audit_document_lists_remaining_release_gates(): void
    {
        $path = base_path('docs/modules/Finance/PHASE2_CLOSURE_AUDIT_CP30.md');
        $audit = file_get_contents($path);
        self::assertIsString($audit);
        foreach ([
            'Authoritative settlement mapping',
            'Source lifecycle ownership',
            'Atomic writer and reversal',
            'Concurrency and rollback',
            'Finance regression',
            'UI acceptance',
            'Release authorization',
        ] as $gate) {
            self::assertStringContainsString($gate, $audit);
        }
    }
}
