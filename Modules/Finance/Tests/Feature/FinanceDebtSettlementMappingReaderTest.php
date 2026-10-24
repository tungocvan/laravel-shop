<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementMappingReader;
use Tests\TestCase;

final class FinanceDebtSettlementMappingReaderTest extends TestCase
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
        if (! Schema::hasTable('finance_debt_settlement_mappings')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000003_create_finance_debt_settlement_mappings_table.php'))->up();
        }
    }

    private function fixture(): array
    {
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'OWN-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'OWN-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'OWN-'.uniqid(), 'direction' => 'receipt',
            'status' => 'posted', 'account_id' => $account,
            'currency' => 'VND', 'amount' => '30.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $voucher];
    }

    private function mapping(int $debt, int $voucher, string $operation, ?int $reverses = null): int
    {
        return DB::table('finance_debt_settlement_mappings')->insertGetId([
            'obligation_id' => $debt, 'voucher_id' => $voucher,
            'operation' => $operation, 'amount' => '30.00',
            'reverses_mapping_id' => $reverses,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_absent_mapping_is_not_authoritative(): void
    {
        [$debt, $voucher] = $this->fixture();
        self::assertSame('no_authoritative_mapping', (new FinanceDebtSettlementMappingReader)->inspect($debt, $voucher)['mapping_status']);
    }

    public function test_post_mapping_is_not_settlement_authorization(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->mapping($debt, $voucher, 'post');
        $result = (new FinanceDebtSettlementMappingReader)->inspect($debt, $voucher);
        self::assertSame('post_mapping_requires_lifecycle_contract', $result['mapping_status']);
        self::assertFalse($result['eligible']);
    }

    public function test_reversal_without_origin_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->mapping($debt, $voucher, 'reverse');
        self::assertSame('missing_reversal_origin', (new FinanceDebtSettlementMappingReader)->inspect($debt, $voucher)['mapping_status']);
    }

    public function test_reversal_link_must_reference_same_debt(): void
    {
        [$otherDebt, $originalVoucher] = $this->fixture();
        $original = $this->mapping($otherDebt, $originalVoucher, 'post');
        [$debt, $voucher] = $this->fixture();
        $this->mapping($debt, $voucher, 'reverse', $original);
        self::assertSame('invalid_reversal_origin', (new FinanceDebtSettlementMappingReader)->inspect($debt, $voucher)['mapping_status']);
    }

    public function test_linked_reversal_is_only_structurally_valid(): void
    {
        [$debt, $voucher] = $this->fixture();
        $original = $this->mapping($debt, $voucher, 'post');
        $this->mapping($debt, $voucher, 'reverse', $original);
        $result = (new FinanceDebtSettlementMappingReader)->inspect($debt, $voucher);
        self::assertSame('multiple_operations_require_review', $result['mapping_status']);
        self::assertFalse($result['eligible']);
    }

    public function test_execution_is_always_blocked(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementMappingReader)->assertCanSettle($debt, $voucher);
    }
}
