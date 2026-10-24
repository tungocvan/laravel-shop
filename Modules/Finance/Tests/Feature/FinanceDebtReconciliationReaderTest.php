<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtReconciliationReader;
use Tests\TestCase;

final class FinanceDebtReconciliationReaderTest extends TestCase
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

    private function fixture(string $status = 'confirmed', bool $linked = true): array
    {
        $identity = 'recon-'.uniqid();
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'REC-'.uniqid(), 'direction' => 'receivable',
            'status' => $status, 'counterparty_name' => 'Customer',
            'original_amount' => '100.00', 'currency' => 'VND',
            'source_module' => $linked ? 'Example' : null,
            'source_type' => $linked ? 'invoice' : null,
            'source_document_id' => $linked ? $identity : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! $linked) {
            return [$id, null, null];
        }
        $sourceId = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Example', 'source_type' => 'invoice',
            'source_document_id' => $identity, 'source_document_code' => 'INV-'.uniqid(),
            'direction' => 'receivable', 'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $accountId = DB::table('finance_accounts')->insertGetId([
            'code' => 'REC-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$id, $sourceId, $accountId];
    }

    private function allocate(int $sourceId, int $accountId, string $status, string $amount): void
    {
        $voucherId = DB::table('finance_vouchers')->insertGetId([
            'number' => 'REC-V-'.uniqid(), 'direction' => 'receipt',
            'status' => $status, 'account_id' => $accountId, 'currency' => 'VND',
            'amount' => $amount, 'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_allocations')->insert([
            'voucher_id' => $voucherId, 'source_document_id' => $sourceId,
            'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_posted_and_draft_are_diagnostic_only_not_confirmed_debt_settlement(): void
    {
        [$id, $source, $account] = $this->fixture();
        $this->allocate($source, $account, 'posted', '30.25');
        $this->allocate($source, $account, 'draft', '40.00');
        $result = (new FinanceDebtReconciliationReader)->forObligation($id);
        self::assertSame('30.25', $result['treasury_posted_amount']);
        self::assertSame('requires_settlement_contract', $result['reconciliation_status']);
        self::assertFalse($result['debt_settlement_confirmed']);
    }

    public function test_adjustment_below_posted_treasury_is_flagged_not_clamped(): void
    {
        [$id, $source, $account] = $this->fixture();
        $this->allocate($source, $account, 'posted', '70.00');
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $id, 'event_type' => 'adjustment',
            'signed_amount' => '-40.00', 'effective_on' => '2026-10-24',
            'reason' => 'Adjustment', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $result = (new FinanceDebtReconciliationReader)->forObligation($id);
        self::assertSame('60', (string) (int) $result['adjusted_principal']);
        self::assertSame('treasury_exceeds_adjusted_principal', $result['reconciliation_status']);
        self::assertFalse($result['debt_settlement_confirmed']);
    }

    public function test_manual_debt_has_no_treasury_link(): void
    {
        [$id] = $this->fixture('confirmed', false);
        $result = (new FinanceDebtReconciliationReader)->forObligation($id);
        self::assertNull($result['treasury_posted_amount']);
        self::assertSame('no_treasury_source_link', $result['reconciliation_status']);
    }

    public function test_draft_debt_is_not_confirmed_as_settled(): void
    {
        [$id] = $this->fixture('draft', false);
        $result = (new FinanceDebtReconciliationReader)->forObligation($id);
        self::assertSame('unconfirmed_debt', $result['reconciliation_status']);
        self::assertFalse($result['debt_settlement_confirmed']);
    }

    public function test_missing_debt_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtReconciliationReader)->forObligation(-1);
    }
}
