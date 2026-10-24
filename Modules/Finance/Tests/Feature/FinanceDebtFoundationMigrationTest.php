<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinanceDebtFoundationMigrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    public function test_debt_schema_and_draft_defaults_exist(): void
    {
        self::assertTrue(Schema::hasColumns('finance_debt_obligations', [
            'source_module', 'source_type', 'source_document_id', 'direction',
            'status', 'due_on', 'original_amount', 'counterparty_name',
        ]));
        self::assertTrue(Schema::hasColumns('finance_debt_events', [
            'obligation_id', 'event_type', 'signed_amount', 'effective_on',
        ]));

        $id = $this->insertObligation('1001');
        self::assertSame('draft', DB::table('finance_debt_obligations')->where('id', $id)->value('status'));
    }

    public function test_partner_link_is_nullable_and_source_identity_index_exists(): void
    {
        // The partner column belongs to the additive second migration. In test
        // databases created from the first migration only, apply it explicitly.
        if (! Schema::hasColumn('finance_debt_obligations', 'partner_id')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000002_add_partner_id_to_finance_debt_obligations.php'))->up();
        }
        self::assertTrue(Schema::hasColumn('finance_debt_obligations', 'partner_id'));
        $indexes = Schema::getIndexes('finance_debt_obligations');
        $names = array_column($indexes, 'name');
        self::assertContains('finance_debt_source_identity_unique', $names);

        $id = $this->insertObligation(null);
        self::assertNull(DB::table('finance_debt_obligations')->where('id', $id)->value('partner_id'));
    }

    public function test_source_identity_allows_distinct_source_types(): void
    {
        $this->insertObligation('SOURCE-42');
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'source_module' => 'Invoices',
            'source_type' => 'credit_note',
            'source_document_id' => 'SOURCE-42',
            'reference_number' => 'CREDIT-'.uniqid(),
            'direction' => 'receivable',
            'counterparty_name' => 'Test partner',
            'original_amount' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        self::assertGreaterThan(0, $id);
    }

    public function test_same_invoice_cannot_be_linked_twice(): void
    {
        $this->insertObligation('2001');

        $this->expectException(QueryException::class);
        $this->insertObligation('2001');
    }

    public function test_manual_debts_without_source_can_coexist(): void
    {
        $first = $this->insertObligation(null);
        $second = $this->insertObligation(null);

        self::assertNotSame($first, $second);
        self::assertSame(2, DB::table('finance_debt_obligations')->whereIn('id', [$first, $second])->count());
    }

    private function insertObligation(?string $sourceId): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'source_module' => $sourceId === null ? null : 'Invoices',
            'source_type' => $sourceId === null ? null : 'invoice',
            'source_document_id' => $sourceId,
            'reference_number' => 'DEBT-'.uniqid(),
            'direction' => 'receivable',
            'counterparty_name' => 'Test partner',
            'original_amount' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
