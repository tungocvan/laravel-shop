<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtAllocationOwnership;
use Modules\Finance\Services\FinanceDebtAtomicSettlementGate;
use Modules\Finance\Services\FinanceDebtSettlementTransactionProtocol;
use Tests\TestCase;

/** CP33 fail-closed integration contract; no real settlement writer is enabled. */
final class FinanceDebtAllocationOwnershipBoundaryTest extends TestCase
{
    public function test_invalid_exact_allocation_identity_is_rejected_before_database_access(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtAllocationOwnership)->inspect(1, 1, 0);
    }

    public function test_exact_allocation_preflight_is_read_only_and_never_authoritative(): void
    {
        $source = file_get_contents(base_path('Modules/Finance/Services/FinanceDebtAllocationOwnership.php'));
        self::assertIsString($source);
        self::assertStringContainsString("where('id', \$allocationId)", $source);
        self::assertStringContainsString("where('id', \$voucherId)", $source);
        self::assertStringContainsString("'eligible' => false", $source);
        self::assertStringContainsString("'authoritative' => false", $source);
        self::assertStringContainsString('manual_debt_requires_explicit_payment_link', $source);
        self::assertStringContainsString('allocation_does_not_belong_to_debt_and_voucher', $source);
        foreach (['->insert(', '->update(', '->delete(', '->reserve('] as $writer) {
            self::assertStringNotContainsString($writer, $source);
        }
    }

    public function test_existing_atomic_gate_still_denies_execution(): void
    {
        self::assertFalse((new FinanceDebtSettlementTransactionProtocol)->plan(1, 1, 'cp33-guard-key')['ready']);
        $source = file_get_contents(base_path('Modules/Finance/Services/FinanceDebtAtomicSettlementGate.php'));
        self::assertIsString($source);
        self::assertStringContainsString('Atomic settlement blocked:', $source);
        self::assertStringNotContainsString('->reserve(', $source);
    }

    public function test_cp33_document_does_not_claim_writer_or_ui_readiness(): void
    {
        $document = file_get_contents(base_path('docs/modules/Finance/PHASE2_ALLOCATION_OWNERSHIP_CP33.md'));
        self::assertIsString($document);
        foreach (['NOT SETTLEMENT READY', 'exact allocation', 'manual debt', 'no writer', 'CHECKPOINT TEST UI'] as $term) {
            self::assertStringContainsString($term, $document);
        }
    }
}
