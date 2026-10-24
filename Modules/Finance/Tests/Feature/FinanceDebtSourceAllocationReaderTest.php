<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSourceAllocationReader;
use Tests\TestCase;

final class FinanceDebtSourceAllocationReaderTest extends TestCase
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

    private function fixture(bool $mismatch = false): array
    {
        $identity = 'alloc-'.uniqid();
        $debtId = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'DEBT-'.uniqid(),
            'source_module' => 'Example', 'source_type' => 'invoice', 'source_document_id' => $identity,
            'direction' => 'receivable', 'status' => 'confirmed',
            'counterparty_name' => 'Customer', 'currency' => 'VND',
            'original_amount' => $mismatch ? '99.00' : '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $sourceId = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Example', 'source_type' => 'invoice', 'source_document_id' => $identity,
            'source_document_code' => 'INV-'.uniqid(), 'direction' => 'receivable',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $accountId = DB::table('finance_accounts')->insertGetId([
            'code' => 'ACC-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debtId, $sourceId, $accountId];
    }

    private function allocate(int $sourceId, int $accountId, string $status, string $amount): void
    {
        $voucherId = DB::table('finance_vouchers')->insertGetId([
            'number' => 'V-'.uniqid(), 'direction' => 'receipt', 'status' => $status,
            'account_id' => $accountId, 'currency' => 'VND', 'amount' => $amount,
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_allocations')->insert([
            'voucher_id' => $voucherId, 'source_document_id' => $sourceId,
            'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_only_posted_allocations_count_and_reversals_remove_effect(): void
    {
        [$debtId, $sourceId, $accountId] = $this->fixture();
        $this->allocate($sourceId, $accountId, 'posted', '30.25');
        $this->allocate($sourceId, $accountId, 'draft', '20.00');
        $this->allocate($sourceId, $accountId, 'reversed', '10.00');
        $result = (new FinanceDebtSourceAllocationReader)->forObligation($debtId);
        self::assertSame('30.25', $result['posted_amount']);
        self::assertSame(1, $result['posted_count']);
        self::assertSame(1, $result['draft_count']);
        self::assertSame(1, $result['reversed_count']);
    }

    public function test_source_financial_mismatch_fails_closed(): void
    {
        [$debtId] = $this->fixture(true);
        $this->expectException(ValidationException::class);
        (new FinanceDebtSourceAllocationReader)->forObligation($debtId);
    }

    public function test_overallocated_posted_source_fails_closed(): void
    {
        [$debtId, $sourceId, $accountId] = $this->fixture();
        $this->allocate($sourceId, $accountId, 'posted', '80.00');
        $this->allocate($sourceId, $accountId, 'posted', '30.00');
        $this->expectException(ValidationException::class);
        (new FinanceDebtSourceAllocationReader)->forObligation($debtId);
    }

    public function test_manual_debt_has_no_source_projection(): void
    {
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'MAN-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'original_amount' => '100.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        self::assertNull((new FinanceDebtSourceAllocationReader)->forObligation($id));
    }
}
