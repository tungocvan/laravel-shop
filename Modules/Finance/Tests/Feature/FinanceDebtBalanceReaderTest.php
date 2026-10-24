<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtBalanceReader;
use Tests\TestCase;

final class FinanceDebtBalanceReaderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    public function test_projection_includes_all_signed_events_without_mutating_records(): void
    {
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'BAL-'.uniqid(),
            'direction' => 'receivable',
            'counterparty_name' => 'Customer',
            'status' => 'confirmed',
            'original_amount' => '100.25',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['10.50', '-40.25'] as $signed) {
            DB::table('finance_debt_events')->insert([
                'obligation_id' => $id,
                'event_type' => 'adjustment',
                'signed_amount' => $signed,
                'effective_on' => '2026-10-24',
                'reason' => 'Projection test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $result = (new FinanceDebtBalanceReader)->forObligation($id);
        self::assertEquals(100.25, (float) $result['original_amount']);
        self::assertEquals(-29.75, (float) $result['adjustment_total']);
        self::assertEquals(70.50, (float) $result['adjusted_principal']);
        self::assertSame(2, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }

    public function test_missing_obligation_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtBalanceReader)->forObligation(-1);
    }
}
