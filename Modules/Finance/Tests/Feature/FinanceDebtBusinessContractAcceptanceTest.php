<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementIntegrity;
use Modules\Finance\Services\FinanceDebtSettlementTransactionProtocol;
use Modules\Finance\Services\FinanceDebtConcurrencyReadiness;
use Tests\TestCase;

/**
 * CP31: executable acceptance boundary for proposed business contract.
 * These checks do not authorize settlement or approve unresolved policies.
 */
final class FinanceDebtBusinessContractAcceptanceTest extends TestCase
{
    public function test_partial_and_exact_remaining_amount_are_structural_only(): void
    {
        $integrity = new FinanceDebtSettlementIntegrity;
        foreach (['1.00', '80.00'] as $amount) {
            $result = $integrity->inspect('100.00', '20.00', $amount, 'cp31-partial-001');
            self::assertTrue($result['valid']);
            self::assertSame('requires_atomic_idempotency_and_authoritative_settlement_mapping', $result['reason']);
        }
        $this->expectException(ValidationException::class);
        $integrity->assertCanExecute('100.00', '20.00', '80.00', 'cp31-partial-001');
    }

    public function test_overpayment_and_duplicate_token_fail_preflight(): void
    {
        $integrity = new FinanceDebtSettlementIntegrity;
        self::assertSame(
            'payment_exceeds_remaining_principal',
            $integrity->inspect('100.00', '20.00', '80.01', 'cp31-overpay-001')['reason']
        );
        self::assertSame(
            'duplicate_idempotency_key',
            $integrity->inspect('100.00', '20.00', '10.00', 'cp31-duplicate-001', true)['reason']
        );
    }

    public function test_post_and_reversal_remain_unapproved_even_with_valid_protocol(): void
    {
        $protocol = new FinanceDebtSettlementTransactionProtocol;
        foreach (['post', 'reverse'] as $operation) {
            $plan = $protocol->plan(1, 2, 'cp31-operation-001', $operation);
            self::assertFalse($plan['ready']);
            self::assertSame('authoritative_mapping_and_atomic_storage_not_implemented', $plan['reason']);
            try {
                $protocol->assertCanExecute(1, 2, 'cp31-operation-001', $operation);
                self::fail('CP31 must not authorize '.$operation);
            } catch (ValidationException $exception) {
                self::assertNotEmpty($exception->errors()['settlement'] ?? []);
            }
        }
        self::assertFalse((new FinanceDebtConcurrencyReadiness)->inspect()['eligible']);
    }

    public function test_contract_records_unresolved_business_decisions_and_keeps_gate_closed(): void
    {
        $path = base_path('docs/modules/Finance/PHASE2_BUSINESS_CONTRACT_CP31.md');
        $document = file_get_contents($path);
        self::assertIsString($document);
        foreach ([
            'PROPOSED — REQUIRES BUSINESS APPROVAL',
            'Source ownership',
            'Partial and multi-source allocation',
            'Currency and direction',
            'Outstanding balance',
            'Reversal identity',
            'Lifecycle and cancellation',
            'Permissions and audit',
            'Unresolved decisions',
            'No settlement activation',
        ] as $required) {
            self::assertStringContainsString($required, $document);
        }
        $gate = file_get_contents(base_path('Modules/Finance/Services/FinanceDebtAtomicSettlementGate.php'));
        self::assertIsString($gate);
        self::assertStringContainsString('Atomic settlement blocked:', $gate);
        self::assertStringNotContainsString('->reserve(', $gate);
    }
}
