<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementBalanceInvariant;
use Tests\TestCase;

final class FinanceDebtSettlementBalanceInvariantTest extends TestCase
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

    private function debt(): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'CP18-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function mapping(int $debt, string $operation, string $amount, ?int $origin = null): int
    {
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'CP18-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'CP18-'.uniqid(), 'direction' => 'receipt',
            'status' => $operation === 'post' ? 'posted' : 'reversed',
            'account_id' => $account, 'currency' => 'VND',
            'amount' => $amount, 'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('finance_debt_settlement_mappings')->insertGetId([
            'obligation_id' => $debt, 'voucher_id' => $voucher,
            'operation' => $operation, 'amount' => $amount,
            'reverses_mapping_id' => $origin,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_no_mappings_is_diagnostic_only(): void
    {
        $result = (new FinanceDebtSettlementBalanceInvariant)->inspect($this->debt());
        self::assertFalse($result['eligible']);
        self::assertSame('100.00', $result['remaining_diagnostic_amount']);
    }

    public function test_post_and_owned_reversal_restore_balance(): void
    {
        $debt = $this->debt();
        $origin = $this->mapping($debt, 'post', '30.00');
        $this->mapping($debt, 'reverse', '30.00', $origin);
        $result = (new FinanceDebtSettlementBalanceInvariant)->inspect($debt);
        self::assertSame('0.00', $result['net_mapped_amount']);
        self::assertSame('100.00', $result['remaining_diagnostic_amount']);
        self::assertFalse($result['eligible']);
    }

    public function test_excessive_posts_fail_closed(): void
    {
        $debt = $this->debt();
        $this->mapping($debt, 'post', '70.00');
        $this->mapping($debt, 'post', '70.00');
        self::assertSame('negative_or_excessive_mapping_balance',
            (new FinanceDebtSettlementBalanceInvariant)->inspect($debt)['reason_code']);
    }

    public function test_reversal_of_another_debt_fails_closed(): void
    {
        $origin = $this->mapping($this->debt(), 'post', '30.00');
        $debt = $this->debt();
        $this->mapping($debt, 'reverse', '30.00', $origin);
        self::assertSame('invalid_reversal_ownership',
            (new FinanceDebtSettlementBalanceInvariant)->inspect($debt)['reason_code']);
    }

    public function test_mismatched_reversal_amount_fails_closed(): void
    {
        $debt = $this->debt();
        $origin = $this->mapping($debt, 'post', '30.00');
        $this->mapping($debt, 'reverse', '20.00', $origin);
        self::assertSame('invalid_reversal_ownership',
            (new FinanceDebtSettlementBalanceInvariant)->inspect($debt)['reason_code']);
    }

    public function test_execution_remains_disabled(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementBalanceInvariant)->assertCanSettle($this->debt());
    }
}
