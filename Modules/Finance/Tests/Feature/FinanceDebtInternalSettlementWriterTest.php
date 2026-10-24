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
            ['finance_debt_settlement_audit_events', '2026_10_24_000005_create_finance_debt_settlement_audit_events_table.php'],
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
        self::assertSame('30.00', number_format((float) DB::table('finance_debt_settlement_mappings')->where('id', $id)->value('amount'), 2, '.', ''));
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

    public function test_full_reversal_preserves_origin_ownership_and_restores_diagnostic_balance(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp35-post-key-001', 11, 'Payment', fn () => true);
        DB::table('finance_vouchers')->where('id', $voucher)->update(['status' => 'reversed']);
        $reversal = $writer->reverse($origin, $voucher, 'cp35-reverse-key-001', 12, 'Full reversal', fn () => true);
        self::assertSame($origin, (int) DB::table('finance_debt_settlement_mappings')->where('id', $reversal)->value('reverses_mapping_id'));
        self::assertSame(1, DB::table('finance_debt_allocation_ownerships')->where('mapping_id', $origin)->count());
        self::assertSame('0.00', (new \Modules\Finance\Services\FinanceDebtSettlementBalanceInvariant)->inspect($debt)['net_mapped_amount']);
        self::assertSame(2, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
    }

    public function test_duplicate_reversal_rolls_back_second_token(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp35-post-key-002', 11, 'Payment', fn () => true);
        DB::table('finance_vouchers')->where('id', $voucher)->update(['status' => 'reversed']);
        $writer->reverse($origin, $voucher, 'cp35-reverse-key-002', 12, 'First reversal', fn () => true);
        try {
            $writer->reverse($origin, $voucher, 'cp35-reverse-key-003', 12, 'Second reversal', fn () => true);
            self::fail('Duplicate reversal unexpectedly passed.');
        } catch (ValidationException $exception) {
            self::assertSame(2, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(2, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_reversal_requires_original_voucher_to_be_reversed(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp35-post-key-003', 11, 'Payment', fn () => true);
        try {
            $writer->reverse($origin, $voucher, 'cp35-reverse-key-004', 12, 'Not reversed', fn () => true);
            self::fail('Posted voucher reversal unexpectedly passed.');
        } catch (ValidationException $exception) {
            self::assertSame(1, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_reversal_is_not_activated(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtInternalSettlementWriter)->reverse(999999, 999999, 'cp34-reverse-key', 11, 'Reversal', fn () => true);
    }
    public function test_cp36_ownership_audit_reconciles_post_and_full_reversal(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $audit = new \Modules\Finance\Services\FinanceDebtSettlementOwnershipAudit;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp36-post-key-001', 21, 'Authorized payment', fn () => true);
        self::assertSame('ownership_reconciled_diagnostic_only', $audit->inspect($debt)['reason_code']);
        DB::table('finance_vouchers')->where('id', $voucher)->update(['status' => 'reversed']);
        $writer->reverse($origin, $voucher, 'cp36-reverse-key-001', 22, 'Authorized reversal', fn () => true);
        $result = $audit->inspect($debt);
        self::assertSame('ownership_reconciled_diagnostic_only', $result['reason_code']);
        self::assertSame(2, $result['mapping_count']);
        self::assertSame(1, $result['ownership_count']);
        self::assertFalse($result['eligible']);
    }

    public function test_cp36_ownership_audit_detects_changed_allocation(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        (new FinanceDebtInternalSettlementWriter)->post(
            $debt, $voucher, $allocation, 'cp36-post-key-002', 21, 'Payment', fn () => true
        );
        DB::table('finance_allocations')->where('id', $allocation)->update(['amount' => '29.00']);
        self::assertSame(
            'allocation_ownership_mismatch',
            (new \Modules\Finance\Services\FinanceDebtSettlementOwnershipAudit)->inspect($debt)['reason_code']
        );
    }

    public function test_cp36_outer_transaction_failure_rolls_back_post_and_token(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        try {
            DB::transaction(function () use ($debt, $voucher, $allocation): void {
                (new FinanceDebtInternalSettlementWriter)->post(
                    $debt, $voucher, $allocation, 'cp36-post-key-003', 21, 'Rollback', fn () => true
                );
                throw new \RuntimeException('Injected failure after posting');
            });
            self::fail('Expected injected failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame(0, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_allocation_ownerships')->where('obligation_id', $debt)->count());
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
        }
    }

    public function test_cp36_outer_transaction_failure_rolls_back_reversal_and_token(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp36-post-key-004', 21, 'Payment', fn () => true);
        DB::table('finance_vouchers')->where('id', $voucher)->update(['status' => 'reversed']);
        try {
            DB::transaction(function () use ($writer, $origin, $voucher): void {
                $writer->reverse($origin, $voucher, 'cp36-reverse-key-004', 22, 'Rollback', fn () => true);
                throw new \RuntimeException('Injected failure after reversal');
            });
            self::fail('Expected injected failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame(1, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
            self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $debt)->count());
            self::assertSame(1, DB::table('finance_debt_allocation_ownerships')->where('obligation_id', $debt)->count());
        }
    }

    public function test_cp37_audit_persists_actor_reason_origin_and_token_for_both_operations(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        $writer = new FinanceDebtInternalSettlementWriter;
        $origin = $writer->post($debt, $voucher, $allocation, 'cp37-post-key-001', 41, 'Verified payment', fn () => true);
        DB::table('finance_vouchers')->where('id', $voucher)->update(['status' => 'reversed']);
        $reversal = $writer->reverse($origin, $voucher, 'cp37-reverse-key-001', 42, 'Verified reversal', fn () => true);
        $postAudit = DB::table('finance_debt_settlement_audit_events')->where('mapping_id', $origin)->first();
        $reverseAudit = DB::table('finance_debt_settlement_audit_events')->where('mapping_id', $reversal)->first();
        self::assertSame(41, (int) $postAudit->actor_id);
        self::assertSame('Verified payment', $postAudit->reason);
        self::assertNull($postAudit->origin_mapping_id);
        self::assertSame(42, (int) $reverseAudit->actor_id);
        self::assertSame('Verified reversal', $reverseAudit->reason);
        self::assertSame($origin, (int) $reverseAudit->origin_mapping_id);
        self::assertSame('cp37-reverse-key-001', $reverseAudit->idempotency_key);
    }

    public function test_cp37_failed_authorization_does_not_write_audit(): void
    {
        [$debt, $voucher, $allocation] = $this->fixture();
        try {
            (new FinanceDebtInternalSettlementWriter)->post(
                $debt, $voucher, $allocation, 'cp37-post-key-002', 41, 'Unauthorized', fn () => false
            );
            self::fail('Expected authorization failure.');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('finance_debt_settlement_audit_events')->where('obligation_id', $debt)->count());
        }
    }

}
