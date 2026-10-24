<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtAtomicSettlementGate;
use Modules\Finance\Services\FinanceDebtConcurrencyReadiness;
use Tests\TestCase;

final class FinanceDebtConcurrencyReadinessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['finance_accounts', '2026_10_21_000001_create_finance_foundation_tables.php'],
            ['finance_debt_obligations', '2026_10_24_000001_create_finance_debt_foundation_tables.php'],
            ['finance_debt_idempotency_keys', '2026_10_24_000002_create_finance_debt_idempotency_keys_table.php'],
        ] as [$table, $file]) {
            if (! Schema::hasTable($table)) {
                (require base_path('Modules/Finance/database/migrations/'.$file))->up();
            }
        }
    }

    private function fixture(): array
    {
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'CP21-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'CP21-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'CP21-'.uniqid(), 'direction' => 'receipt',
            'status' => 'posted', 'account_id' => $account,
            'currency' => 'VND', 'amount' => '30.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $voucher];
    }

    public function test_readiness_never_grants_settlement(): void
    {
        $result = (new FinanceDebtConcurrencyReadiness)->inspect();
        self::assertFalse($result['eligible']);
        self::assertTrue($result['requires_independent_connections']);
        self::assertTrue($result['requires_duplicate_key_race']);
        self::assertTrue($result['requires_reversal_ownership_race']);
        self::assertContains($result['reason_code'], [
            'independent_connection_contention_test_required',
            'database_driver_not_suitable_for_row_lock_proof',
        ]);
    }

    public function test_readiness_assertion_always_denies(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtConcurrencyReadiness)->assertReadyForSettlement();
    }

    public function test_failed_settlement_does_not_change_ledger_keys_or_voucher(): void
    {
        [$debt, $voucher] = $this->fixture();
        $beforeLedger = DB::table('finance_ledger_entries')->where('voucher_id', $voucher)->count();
        try {
            (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'cp21-no-write');
            self::fail('Settlement must remain disabled.');
        } catch (ValidationException $exception) {
            self::assertSame($beforeLedger, DB::table('finance_ledger_entries')->where('voucher_id', $voucher)->count());
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $debt)->count());
            self::assertSame('posted', DB::table('finance_vouchers')->where('id', $voucher)->value('status'));
        }
    }

    public function test_repeated_failed_attempts_do_not_reserve_a_key(): void
    {
        [$debt, $voucher] = $this->fixture();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'cp21-repeat');
                self::fail('Settlement must remain disabled.');
            } catch (ValidationException $exception) {
                self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
            }
        }
    }
}
