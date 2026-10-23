<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use Modules\Finance\Data\FinancialDocumentSnapshot;
use Modules\Finance\Services\FinancialDocumentRegistry;
use Modules\Finance\Services\VoucherPostingService;
use Tests\TestCase;

final class VoucherPostingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $migration = require base_path('Modules/Finance/database/migrations/2026_10_21_000001_create_finance_foundation_tables.php');
        if (! Schema::hasTable('finance_accounts')) {
            $migration->up();
        }
    }

    public function test_partial_posting_prevents_overpayment_and_reversal_preserves_ledger(): void
    {
        $registry = new FinancialDocumentRegistry;
        $registry->register($this->provider());
        $service = new VoucherPostingService($registry);

        $first = $this->makeVoucher('PT-001', '60.00');
        $service->post($first, 1);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $first, 'status' => 'posted']);
        $this->assertDatabaseHas('finance_ledger_entries', ['voucher_id' => $first, 'entry_type' => 'posting', 'signed_amount' => '60.00']);

        $second = $this->makeVoucher('PT-002', '50.00');
        try {
            $service->post($second, 1);
            $this->fail('Overpayment should have been rejected.');
        } catch (ValidationException $e) {
            $this->assertDatabaseHas('finance_vouchers', ['id' => $second, 'status' => 'draft']);
        }

        $service->reverse($first, 2);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $first, 'status' => 'reversed']);
        $this->assertDatabaseHas('finance_ledger_entries', ['voucher_id' => $first, 'entry_type' => 'reversal', 'signed_amount' => '-60.00']);

        $service->post($second, 3);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $second, 'status' => 'posted']);
        $this->assertSame(3, DB::table('finance_ledger_entries')->count());
    }

    public function test_missing_provider_blocks_posting(): void
    {
        $id = $this->makeVoucher('PT-003', '20.00');
        $this->expectException(\RuntimeException::class);
        (new VoucherPostingService(new FinancialDocumentRegistry))->post($id, 1);
    }

    private function provider(): FinancialDocumentProvider
    {
        return new class implements FinancialDocumentProvider {
            public function module(): string { return 'Example'; }
            public function documentType(): string { return 'invoice'; }
            public function resolveForPosting(string $documentId): FinancialDocumentSnapshot
            {
                return new FinancialDocumentSnapshot('Example', 'invoice', $documentId, 'INV-1', 'receivable', 'VND', '100.00');
            }
        };
    }

    private function makeVoucher(string $number, string $amount): int
    {
        $account = DB::table('finance_accounts')->first();
        $accountId = $account?->id ?? DB::table('finance_accounts')->insertGetId([
            'code' => 'CASH', 'name' => 'Cash', 'kind' => 'cash', 'currency' => 'VND',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $source = DB::table('finance_source_documents')->first();
        $sourceId = $source?->id ?? DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Example', 'source_type' => 'invoice',
            'source_document_id' => '1', 'source_document_code' => 'INV-1',
            'direction' => 'receivable', 'currency' => 'VND', 'original_amount' => '100.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('finance_vouchers')->insertGetId([
            'number' => $number, 'direction' => 'receipt', 'status' => 'draft',
            'account_id' => $accountId, 'currency' => 'VND', 'amount' => $amount,
            'voucher_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_allocations')->insert([
            'voucher_id' => $id, 'source_document_id' => $sourceId, 'amount' => $amount,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
