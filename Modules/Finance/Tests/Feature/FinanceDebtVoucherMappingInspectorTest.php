<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtVoucherMappingInspector;
use Tests\TestCase;

final class FinanceDebtVoucherMappingInspectorTest extends TestCase
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

    private function fixture(string $voucherStatus = 'posted', bool $linked = true, bool $allocated = true): array
    {
        $identity = 'map-'.uniqid();
        $debtId = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'MAP-'.uniqid(), 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'source_module' => $linked ? 'Example' : null,
            'source_type' => $linked ? 'invoice' : null,
            'source_document_id' => $linked ? $identity : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $sourceId = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Example', 'source_type' => 'invoice',
            'source_document_id' => $identity, 'source_document_code' => 'INV-'.uniqid(),
            'direction' => 'receivable', 'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $accountId = DB::table('finance_accounts')->insertGetId([
            'code' => 'MAP-'.uniqid(), 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucherId = DB::table('finance_vouchers')->insertGetId([
            'number' => 'MAP-V-'.uniqid(), 'direction' => 'receipt',
            'status' => $voucherStatus, 'account_id' => $accountId,
            'currency' => 'VND', 'amount' => '30.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($allocated) {
            DB::table('finance_allocations')->insert([
                'voucher_id' => $voucherId, 'source_document_id' => $sourceId,
                'amount' => '30.00', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return [$debtId, $voucherId];
    }

    public function test_posted_source_mapping_does_not_authorize_debt_settlement(): void
    {
        [$debtId, $voucherId] = $this->fixture();
        $result = (new FinanceDebtVoucherMappingInspector)->inspect($debtId, $voucherId);
        self::assertSame('source_mapping_only_contract_required', $result['mapping_status']);
        self::assertFalse($result['eligible']);
    }

    public function test_draft_and_reversed_are_not_settled(): void
    {
        foreach (['draft' => 'draft_not_settled', 'reversed' => 'reversed_not_settled'] as $status => $expected) {
            [$debtId, $voucherId] = $this->fixture($status);
            self::assertSame($expected, (new FinanceDebtVoucherMappingInspector)->inspect($debtId, $voucherId)['mapping_status']);
        }
    }

    public function test_missing_allocation_is_rejected(): void
    {
        [$debtId, $voucherId] = $this->fixture('posted', true, false);
        self::assertSame('missing_or_ambiguous_voucher_allocation', (new FinanceDebtVoucherMappingInspector)->inspect($debtId, $voucherId)['mapping_status']);
    }

    public function test_manual_debt_cannot_claim_source_voucher(): void
    {
        [$debtId, $voucherId] = $this->fixture('posted', false);
        self::assertSame('no_authoritative_source_mapping', (new FinanceDebtVoucherMappingInspector)->inspect($debtId, $voucherId)['mapping_status']);
    }

    public function test_mapping_command_remains_blocked(): void
    {
        [$debtId, $voucherId] = $this->fixture();
        $this->expectException(ValidationException::class);
        (new FinanceDebtVoucherMappingInspector)->assertCanMap($debtId, $voucherId);
    }
}
