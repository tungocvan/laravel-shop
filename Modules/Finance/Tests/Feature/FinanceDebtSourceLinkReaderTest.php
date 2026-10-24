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

    public function test_unknown_source_does_not_create_link_or_allocation(): void
    {
        $id = $this->debt('Invoices', 'invoice', 'unknown-'.uniqid());
        self::assertNull((new FinanceDebtSourceLinkReader)->forObligation($id));
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }
}
