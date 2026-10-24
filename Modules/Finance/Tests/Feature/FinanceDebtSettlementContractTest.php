<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementContract;
use Tests\TestCase;

final class FinanceDebtSettlementContractTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_source_documents')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_21_000001_create_finance_foundation_tables.php'))->up();
        }
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    private function debt(string $status = 'confirmed', bool $linked = false): array
    {
        $identity = 'contract-'.uniqid();
        $debtId = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'CON-'.uniqid(),
            'direction' => 'receivable', 'status' => $status,
            'counterparty_name' => 'Customer', 'currency' => 'VND',
            'original_amount' => '100.00',
            'source_module' => $linked ? 'Example' : null,
            'source_type' => $linked ? 'invoice' : null,
            'source_document_id' => $linked ? $identity : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! $linked) {
            return [$debtId, null];
        }
        $sourceId = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Example', 'source_type' => 'invoice',
            'source_document_id' => $identity, 'source_document_code' => 'INV-'.uniqid(),
            'direction' => 'receivable', 'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debtId, $sourceId];
    }

    public function test_draft_debt_is_blocked(): void
    {
        [$id] = $this->debt('draft');
        $result = (new FinanceDebtSettlementContract)->inspect($id);
        self::assertFalse($result['eligible']);
        self::assertSame('debt_not_confirmed', $result['reason_code']);
    }

    public function test_manual_debt_cannot_infer_payment_from_treasury(): void
    {
        [$id] = $this->debt();
        $result = (new FinanceDebtSettlementContract)->inspect($id);
        self::assertFalse($result['eligible']);
        self::assertNull($result['treasury_posted_amount']);
        self::assertSame('missing_authoritative_allocation_mapping', $result['reason_code']);
    }

    public function test_exact_source_link_does_not_authorize_settlement(): void
    {
        [$id, $sourceId] = $this->debt('confirmed', true);
        $result = (new FinanceDebtSettlementContract)->inspect($id);
        self::assertFalse($result['eligible']);
        self::assertSame($sourceId, $result['source_id']);
        self::assertSame('source_lifecycle_and_settlement_contract_missing', $result['reason_code']);
        self::assertSame('0.00', $result['treasury_posted_amount']);
    }

    public function test_command_guard_rejects_even_exact_source_match(): void
    {
        [$id] = $this->debt('confirmed', true);
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementContract)->assertCanSettle($id);
    }

    public function test_missing_debt_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementContract)->inspect(-1);
    }
}
