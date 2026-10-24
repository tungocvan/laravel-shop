<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtSourceLinkReader;
use Tests\TestCase;

final class FinanceDebtSourceLinkReaderTest extends TestCase
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

    private function debt(?string $module, ?string $type, ?string $sourceId): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'LINK-'.uniqid(),
            'direction' => 'receivable',
            'status' => 'draft',
            'counterparty_name' => 'Customer',
            'original_amount' => '100',
            'source_module' => $module,
            'source_type' => $type,
            'source_document_id' => $sourceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_manual_debt_has_no_treasury_source_link(): void
    {
        $id = $this->debt(null, null, null);
        self::assertNull((new FinanceDebtSourceLinkReader)->forObligation($id));
    }

    public function test_partial_source_identity_is_rejected(): void
    {
        $id = $this->debt('Invoices', 'invoice', null);
        $this->expectException(ValidationException::class);
        (new FinanceDebtSourceLinkReader)->forObligation($id);
    }

    public function test_missing_debt_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtSourceLinkReader)->forObligation(-1);
    }

    public function test_exact_source_identity_resolves_without_writing_allocation(): void
    {
        $sourceId = 'exact-'.uniqid();
        $id = $this->debt('Invoices', 'invoice', $sourceId);
        $treasuryId = DB::table('finance_source_documents')->insertGetId([
            'source_module' => 'Invoices',
            'source_type' => 'invoice',
            'source_document_id' => $sourceId,
            'source_document_code' => 'INV-'.uniqid(),
            'direction' => 'receivable',
            'currency' => 'VND',
            'original_amount' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = (new FinanceDebtSourceLinkReader)->forObligation($id);
        self::assertSame($id, $result['debt_id']);
        self::assertSame($treasuryId, $result['source_id']);
        self::assertSame($sourceId, $result['source_document_id']);
        self::assertSame(0, DB::table('finance_allocations')->where('source_document_id', $treasuryId)->count());
    }

    public function test_unknown_source_does_not_create_link_or_allocation(): void
    {
        $id = $this->debt('Invoices', 'invoice', 'unknown-'.uniqid());
        self::assertNull((new FinanceDebtSourceLinkReader)->forObligation($id));
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }
}
