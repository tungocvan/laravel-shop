<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtMappingReconciliation;
use Tests\TestCase;

final class FinanceDebtMappingReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['finance_accounts', '2026_10_21_000001_create_finance_foundation_tables.php'],
            ['finance_debt_obligations', '2026_10_24_000001_create_finance_debt_foundation_tables.php'],
            ['finance_debt_settlement_mappings', '2026_10_24_000003_create_finance_debt_settlement_mappings_table.php'],
        ] as [$table, $migration]) {
            if (! Schema::hasTable($table)) {
                (require base_path('Modules/Finance/database/migrations/'.$migration))->up();
            }
        }
    }

    private function debt(string $status = 'confirmed'): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'CP32-'.uniqid(), 'direction' => 'receivable',
            'status' => $status, 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createPostMapping(int $debt, string $amount): void
    {
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'CP32-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'CP32-'.uniqid(), 'direction' => 'receipt',
            'status' => 'posted', 'account_id' => $account,
            'currency' => 'VND', 'amount' => $amount,
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_debt_settlement_mappings')->insert([
            'obligation_id' => $debt, 'voucher_id' => $voucher,
            'operation' => 'post', 'amount' => $amount,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_empty_mapping_is_diagnostic_not_authoritative(): void
    {
        $result = (new FinanceDebtMappingReconciliation)->inspect($this->debt());
        self::assertFalse($result['eligible']);
        self::assertFalse($result['authoritative']);
        self::assertSame('mapping_allocation_ownership_not_proven', $result['reason_code']);
        self::assertSame('100.00', $result['remaining_diagnostic']);
    }

    public function test_partial_mapping_never_grants_settlement(): void
    {
        $debt = $this->debt();
        $this->createPostMapping($debt, '30.00');
        $result = (new FinanceDebtMappingReconciliation)->inspect($debt);
        self::assertSame('30.00', $result['net_mapped_diagnostic']);
        self::assertSame('70.00', $result['remaining_diagnostic']);
        self::assertFalse($result['eligible']);
    }

    public function test_overpayment_suppresses_unreliable_balance(): void
    {
        $debt = $this->debt();
        $this->createPostMapping($debt, '70.00');
        $this->createPostMapping($debt, '70.00');
        $result = (new FinanceDebtMappingReconciliation)->inspect($debt);
        self::assertSame('negative_or_excessive_mapping_balance', $result['reason_code']);
        self::assertNull($result['remaining_diagnostic']);
        self::assertFalse($result['authoritative']);
    }

    public function test_draft_debt_and_missing_id_fail_closed(): void
    {
        $result = (new FinanceDebtMappingReconciliation)->inspect($this->debt('draft'));
        self::assertSame('debt_not_confirmed', $result['reason_code']);
        $this->expectException(ValidationException::class);
        (new FinanceDebtMappingReconciliation)->inspect(0);
    }

    public function test_execution_is_always_denied(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtMappingReconciliation)->assertCanSettle($this->debt());
    }
}
