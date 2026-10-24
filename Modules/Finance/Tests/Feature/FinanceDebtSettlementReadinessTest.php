<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementReadiness;
use Tests\TestCase;

final class FinanceDebtSettlementReadinessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    private function debt(string $status, bool $sourceLinked = false): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'READY-'.uniqid(),
            'direction' => 'receivable',
            'status' => $status,
            'counterparty_name' => 'Customer',
            'original_amount' => '100.25',
            'source_module' => $sourceLinked ? 'Invoices' : null,
            'source_type' => $sourceLinked ? 'invoice' : null,
            'source_document_id' => $sourceLinked ? uniqid() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_draft_debt_is_not_settlement_eligible(): void
    {
        $result = (new FinanceDebtSettlementReadiness)->inspect($this->debt('draft'));
        self::assertFalse($result['eligible']);
        self::assertSame('Debt must be confirmed before settlement.', $result['reason']);
    }

    public function test_confirmed_manual_debt_is_blocked_until_allocation_mapping_exists(): void
    {
        $id = $this->debt('confirmed');
        $result = (new FinanceDebtSettlementReadiness)->inspect($id);
        self::assertFalse($result['eligible']);
        self::assertSame('Manual debt-to-voucher allocation and reversal mapping are not implemented.', $result['reason']);
        self::assertSame(10025, $this->cents($result['adjusted_principal']));
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }

    public function test_source_linked_debt_is_blocked_without_source_lifecycle_contract(): void
    {
        $result = (new FinanceDebtSettlementReadiness)->inspect($this->debt('confirmed', true));
        self::assertFalse($result['eligible']);
        self::assertSame('Source lifecycle eligibility and debt-to-voucher mapping are not established.', $result['reason']);
    }

    public function test_settlement_command_guard_rejects_confirmed_manual_debt(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementReadiness)->assertCanSettle($this->debt('confirmed'));
    }

    public function test_missing_debt_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementReadiness)->inspect(-1);
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
