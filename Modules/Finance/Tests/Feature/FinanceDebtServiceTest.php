<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtService;
use Tests\TestCase;

final class FinanceDebtServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    public function test_manual_draft_does_not_post_voucher_or_ledger(): void
    {
        $service = new FinanceDebtService;
        $id = $service->createManualDraft('CN-001', 'receivable', 'Customer', '250.50', '2026-11-30', 1);
        $debt = DB::table('finance_debt_obligations')->find($id);
        self::assertSame('draft', $debt->status);
        self::assertSame('250.5', (string) $debt->original_amount);
        self::assertNull($debt->source_module);
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }

    public function test_manual_confirmation_and_append_only_adjustments(): void
    {
        $service = new FinanceDebtService;
        $id = $service->createManualDraft('CN-002', 'payable', 'Supplier', '100.00');
        $service->confirmManualDraft($id, 7, '2026-10-24');
        self::assertSame('confirmed', DB::table('finance_debt_obligations')->find($id)->status);
        $eventId = $service->adjustConfirmedDebt($id, '-20.50', '2026-10-25', 'Discount', 7);
        self::assertSame('adjustment', DB::table('finance_debt_events')->find($eventId)->event_type);
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
        self::assertSame('100', (string) DB::table('finance_debt_obligations')->find($id)->original_amount);
    }

    public function test_duplicate_confirmation_is_rejected(): void
    {
        $service = new FinanceDebtService;
        $id = $service->createManualDraft('CN-003', 'receivable', 'Customer', '100');
        $service->confirmManualDraft($id, 1, '2026-10-24');
        $this->expectException(ValidationException::class);
        $service->confirmManualDraft($id, 1, '2026-10-24');
    }

    public function test_invoice_linked_draft_cannot_be_confirmed(): void
    {
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'source_module' => 'Invoices', 'source_type' => 'invoice', 'source_document_id' => '999',
            'reference_number' => 'INV-999', 'direction' => 'receivable', 'status' => 'draft',
            'counterparty_name' => 'Customer', 'original_amount' => '100',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->expectException(ValidationException::class);
        (new FinanceDebtService)->confirmManualDraft($id, 1, '2026-10-24');
    }

    public function test_adjustment_cannot_make_balance_negative(): void
    {
        $service = new FinanceDebtService;
        $id = $service->createManualDraft('CN-004', 'receivable', 'Customer', '100');
        $service->confirmManualDraft($id, 1, '2026-10-24');
        $service->adjustConfirmedDebt($id, '-60', '2026-10-25', 'Correction', 1);
        try {
            $service->adjustConfirmedDebt($id, '-50', '2026-10-26', 'Excess correction', 1);
            self::fail('Expected negative balance rejection.');
        } catch (ValidationException $e) {
            self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
        }
    }

    public function test_invalid_date_and_amount_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtService)->createManualDraft('CN-005', 'receivable', 'Customer', '1.234', '2026-02-30');
    }
}
