<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtInternalSettlementWriter;
use Tests\TestCase;

final class FinanceDebtInternalSettlementWriterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            ['finance_accounts', '2026_10_21_000001_create_finance_foundation_tables.php'],
            ['finance_debt_obligations', '2026_10_24_000001_create_finance_debt_foundation_tables.php'],
            ['finance_debt_idempotency_keys', '2026_10_24_000002_create_finance_debt_idempotency_keys_table.php'],
            ['finance_debt_settlement_mappings', '2026_10_24_000003_create_finance_debt_settlement_mappings_table.php'],
            ['finance_debt_allocation_ownerships', '2026_10_24_000004_create_finance_debt_allocation_ownerships_table.php'],
        ] as [$table, $file]) {
            if (! Schema::hasTable($table)) {
                (require base_path('Modules/Finance/database/migrations/'.$file))->up();
            }
        }
    }

    private function fixture(string $allocationAmount = '30.00'): array
    {
        $token = 'CP34-'.uniqid();
        $source = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'CP34', 'source_type' => 'invoice',
            'source_document_id' => $token, 'source_document_code' => $token,
            'direction' => 'receivable', 'currency' => 'VND',
            'original_amount' => '100.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'source_module' => 'CP34', 'source_type' => 'invoice',
            'source_document_id' => $token, 'reference_number' => $token,
            'direction' => 'receivable', 'status' => 'confirmed',
            'counterparty_name' => 'Customer', 'currency' => 'VND',
            'original_amount' => '100.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => $token, 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => $token, 'direction' => 'receipt', 'status' => 'posted',
            'account_id' => $account, 'currency' => 'VND',
            'amount' => $allocationAmount, 'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $allocation = DB::table('finance_allocations')->insertGetId([
            'voucher_id' => $voucher, 'source_document_id' => $source,
            'amount' => $allocationAmount, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $voucher, $allocation];
    }

    public function test_internal_post_records_mapping_allocation_owner_and_token_atomically(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $id = (new FinanceDebtInternalSettlementWriter)->post(
            $debt, $voucher, $allocation, 'cp34-post-key-001', 11, 'Approved test', fn () => true
        );
        self::assertSame(1, DB::table('finance_debt_allocation_ownerships')->where('mapping_id', $id)->where('allocation_id', $allocation)->count());
        self::assertSame('30.00', (string) DB::table('finance_debt_settlement_mappings')->where('id', $id)->value('amount'));
        self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $debt)->count());
    }

    public function test_unauthorized_post_never_writes(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        try {
            (new FinanceDebtInternalSettlementWriter)->post(
                $debt, $voucher, $allocation, 'cp34-post-key-002', 11, 'Denied', fn () => false
            );
            self::fail('Authorization unexpectedly passed.');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_repeated_allocation_cannot_be_owned_twice(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $writer->post($debt, $voucher, $allocation, 'cp34-post-key-003', 11, 'First', fn () => true);
        try {
            $writer->post($debt, $voucher, $allocation, 'cp34-post-key-004', 11, 'Second', fn () => true);
            self::fail('Duplicate allocation unexpectedly passed.');
        } catch (ValidationException $exception) {
            self::assertSame(1, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_overpayment_is_rejected_before_token_reservation(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture('101.00');
        try {
            (new FinanceDebtInternalSettlementWriter)->post(
                $debt, $voucher, $allocation, 'cp34-post-key-005', 11, 'Too large', fn () => true
            );
            self::fail('Overpayment unexpectedly passed.');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_reversal_is_not_activated(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtInternalSettlementWriter)->reverse(999999, 999999, 'cp34-reverse-key', 11, 'Reversal', fn () => true);
    }
}
