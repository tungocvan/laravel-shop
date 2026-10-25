<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementMappingIntegrity;
use Tests\TestCase;

final class FinanceDebtSettlementMappingIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['finance_accounts', '2026_10_21_000001_create_finance_foundation_tables.php'],
            ['finance_debt_obligations', '2026_10_24_000001_create_finance_debt_foundation_tables.php'],
            ['finance_debt_settlement_mappings', '2026_10_24_000003_create_finance_debt_settlement_mappings_table.php'],
        ] as [$table, $file]) {
            if (! Schema::hasTable($table)) {
                (require base_path('Modules/Finance/database/migrations/'.$file))->up();
            }
        }
    }

    private function fixture(string $debtStatus = 'confirmed', string $voucherStatus = 'posted', string $voucherCurrency = 'VND'): array
    {
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'AUDIT-'.uniqid(), 'direction' => 'receivable',
            'status' => $debtStatus, 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'AUDIT-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'AUDIT-'.uniqid(), 'direction' => 'receipt',
            'status' => $voucherStatus, 'account_id' => $account,
            'currency' => $voucherCurrency, 'amount' => '80.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $voucher];
    }

    private function mapping(int $debt, int $voucher, string $operation = 'post', string $amount = '30.00', ?int $origin = null): int
    {
        return DB::table('finance_debt_settlement_mappings')->insertGetId([
            'obligation_id' => $debt, 'voucher_id' => $voucher,
            'operation' => $operation, 'amount' => $amount,
            'reverses_mapping_id' => $origin,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_valid_post_shape_is_still_not_authorized(): void
    {
        [$debt, $voucher] = $this->fixture();
        $result = (new FinanceDebtSettlementMappingIntegrity)->inspect($this->mapping($debt, $voucher));
        self::assertSame('post_structure_only_not_authorized', $result['reason_code']);
        self::assertFalse($result['eligible']);
    }

    public function test_unconfirmed_debt_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture('draft');
        self::assertSame('debt_not_confirmed', (new FinanceDebtSettlementMappingIntegrity)->inspect($this->mapping($debt, $voucher))['reason_code']);
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture('confirmed', 'posted', 'USD');
        self::assertSame('voucher_financial_identity_mismatch', (new FinanceDebtSettlementMappingIntegrity)->inspect($this->mapping($debt, $voucher))['reason_code']);
    }

    public function test_draft_voucher_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture('confirmed', 'draft');
        self::assertSame('voucher_lifecycle_mismatch', (new FinanceDebtSettlementMappingIntegrity)->inspect($this->mapping($debt, $voucher))['reason_code']);
    }

    public function test_amount_above_voucher_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture();
        self::assertSame('invalid_or_excessive_amount', (new FinanceDebtSettlementMappingIntegrity)->inspect($this->mapping($debt, $voucher, 'post', '90.00'))['reason_code']);
    }

    public function test_aggregate_mapping_above_principal_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture();
        $first = $this->mapping($debt, $voucher, 'post', '70.00');
        [, $secondVoucher] = $this->fixture();
        $this->mapping($debt, $secondVoucher, 'post', '70.00');
        self::assertSame('aggregate_mapping_amount_invalid', (new FinanceDebtSettlementMappingIntegrity)->inspect($first)['reason_code']);
    }

    public function test_reversal_must_belong_to_same_debt(): void
    {
        [$otherDebt, $originalVoucher] = $this->fixture();
        $origin = $this->mapping($otherDebt, $originalVoucher);
        [$debt, $reversalVoucher] = $this->fixture('confirmed', 'reversed');
        $reversal = $this->mapping($debt, $reversalVoucher, 'reverse', '30.00', $origin);
        self::assertSame('invalid_reversal_ownership', (new FinanceDebtSettlementMappingIntegrity)->inspect($reversal)['reason_code']);
    }

    public function test_execution_remains_blocked(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementMappingIntegrity)->assertCanExecute($this->mapping($debt, $voucher));
    }
}
