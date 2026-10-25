<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSettlementSourceOwnership;
use Tests\TestCase;

final class FinanceDebtSettlementSourceOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_accounts')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_21_000001_create_finance_foundation_tables.php'))->up();
        }
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    private function fixture(): array
    {
        $code = 'CP15-'.uniqid();
        $debt = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => $code, 'direction' => 'receivable',
            'status' => 'confirmed', 'counterparty_name' => 'Customer',
            'currency' => 'VND', 'original_amount' => '100.00',
            'source_module' => 'Invoices', 'source_type' => 'invoice',
            'source_document_id' => $code, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $source = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Invoices', 'source_type' => 'invoice',
            'source_document_id' => $code, 'source_document_code' => $code,
            'direction' => 'receivable', 'currency' => 'VND',
            'original_amount' => '100.00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $account = DB::table('finance_accounts')->insertGetId([
            'code' => $code, 'name' => 'Cash', 'kind' => 'cash',
            'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $voucher = DB::table('finance_vouchers')->insertGetId([
            'number' => $code, 'direction' => 'receipt', 'status' => 'posted',
            'account_id' => $account, 'currency' => 'VND', 'amount' => '80.00',
            'voucher_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debt, $source, $voucher];
    }

    private function allocation(int $voucher, int $source, string $amount): void
    {
        DB::table('finance_allocations')->insert([
            'voucher_id' => $voucher, 'source_document_id' => $source,
            'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_matching_source_is_diagnostic_only(): void
    {
        [$debt, $source, $voucher] = $this->fixture();
        $this->allocation($voucher, $source, '30.00');
        $result = (new FinanceDebtSettlementSourceOwnership)->inspect($debt, $voucher);
        self::assertSame('single_source_allocation_only_not_settlement', $result['reason_code']);
        self::assertFalse($result['eligible']);
    }

    public function test_multi_source_uses_only_exact_allocation(): void
    {
        [$debt, $source, $voucher] = $this->fixture();
        [, $otherSource] = $this->fixture();
        $this->allocation($voucher, $source, '30.00');
        $this->allocation($voucher, $otherSource, '50.00');
        $result = (new FinanceDebtSettlementSourceOwnership)->inspect($debt, $voucher);
        self::assertSame('multi_source_allocation_only_not_settlement', $result['reason_code']);
        self::assertSame('30.00', $result['allocated_amount']);
    }

    public function test_unrelated_source_is_rejected(): void
    {
        [$debt, , $voucher] = $this->fixture();
        [, $otherSource] = $this->fixture();
        $this->allocation($voucher, $otherSource, '30.00');
        self::assertSame('missing_or_ambiguous_source_allocation', (new FinanceDebtSettlementSourceOwnership)->inspect($debt, $voucher)['reason_code']);
    }

    public function test_amount_above_voucher_is_rejected(): void
    {
        [$debt, $source, $voucher] = $this->fixture();
        $this->allocation($voucher, $source, '90.00');
        self::assertSame('invalid_allocation_amount', (new FinanceDebtSettlementSourceOwnership)->inspect($debt, $voucher)['reason_code']);
    }

    public function test_settlement_execution_remains_blocked(): void
    {
        [$debt, $source, $voucher] = $this->fixture();
        $this->allocation($voucher, $source, '30.00');
        $this->expectException(ValidationException::class);
        (new FinanceDebtSettlementSourceOwnership)->assertCanSettle($debt, $voucher);
    }
}
