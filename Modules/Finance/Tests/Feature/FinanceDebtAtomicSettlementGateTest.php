<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtAtomicSettlementGate;
use Tests\TestCase;

final class FinanceDebtAtomicSettlementGateTest extends TestCase
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
        if (! Schema::hasTable('finance_debt_idempotency_keys')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000002_create_finance_debt_idempotency_keys_table.php'))->up();
        }
    }

    private function fixture(string $status = 'posted'): array
    {
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'ATOMIC-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => 'ATOMIC-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => 'ATOMIC-'.uniqid(), 'direction' => 'receipt',
            'status' => $status, 'account_id' => $account,
            'currency' => 'VND', 'amount' => '30.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $voucher];
    }

    public function test_posted_voucher_cannot_settle_debt_or_reserve_key(): void
    {
        [$debt, $voucher] = $this->fixture();
        try {
            (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'atomic-unique-001');
            self::fail('Settlement unexpectedly executed.');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $debt)->count());
            self::assertSame('posted', DB::table('finance_vouchers')->where('id', $voucher)->value('status'));
        }
    }

    public function test_reversed_voucher_cannot_create_debt_reversal(): void
    {
        [$debt, $voucher] = $this->fixture('reversed');
        try {
            (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'atomic-unique-002', 'reverse');
            self::fail('Reversal unexpectedly executed.');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $debt)->count());
        }
    }

    public function test_draft_voucher_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture('draft');
        $this->expectException(ValidationException::class);
        (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'atomic-unique-003');
    }

    public function test_invalid_idempotency_key_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'bad key');
    }

    public function test_unknown_operation_is_rejected(): void
    {
        [$debt, $voucher] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtAtomicSettlementGate)->execute($debt, $voucher, 'atomic-unique-004', 'delete');
    }

    public function test_missing_voucher_is_rejected(): void
    {
        [$debt] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtAtomicSettlementGate)->execute($debt, 999999999, 'atomic-unique-005');
    }
}
