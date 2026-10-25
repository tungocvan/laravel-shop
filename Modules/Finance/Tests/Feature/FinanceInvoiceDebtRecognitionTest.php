<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\InvoiceDebtRecognitionService;
use Tests\TestCase;

final class FinanceInvoiceDebtRecognitionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
        if (! Schema::hasTable('invoices')) {
            (require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php'))->up();
        }
        config()->set('modules.registry.Invoices.enabled', true);
    }

    private function invoice(array $overrides = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'invoice_number' => 'CP09-'.uniqid(),
            'symbol' => 'AA/26E',
            'invoice_type' => 'sold',
            'name' => 'Khách hàng kiểm thử',
            'tax_code' => '0123456789',
            'total_amount' => '125000.00',
            'issued_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_explicit_review_creates_exactly_one_confirmed_source_debt(): void
    {
        $id = $this->invoice();
        $service = new InvoiceDebtRecognitionService;
        self::assertSame([], $service->preview($id)['issues']);
        $debtId = $service->confirm($id, 7, now()->toDateString(), null, true);
        $debt = DB::table('finance_debt_obligations')->find($debtId);
        self::assertSame('Invoices', $debt->source_module);
        self::assertSame((string) $id, $debt->source_document_id);
        self::assertSame('receivable', $debt->direction);
        self::assertSame('confirmed', $debt->status);
        self::assertSame(7, (int) $debt->confirmed_by);
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $debtId)->count());
        self::assertNotEmpty($service->preview($id)['issues']);
        $this->expectException(ValidationException::class);
        $service->confirm($id, 7, now()->toDateString(), null, true);
    }

    public function test_purchase_invoice_creates_payable_only_after_review(): void
    {
        $id = $this->invoice(['invoice_type' => 'purchase']);
        $debtId = (new InvoiceDebtRecognitionService)->confirm($id, 7, now()->toDateString(), null, true);
        self::assertSame('payable', DB::table('finance_debt_obligations')->find($debtId)->direction);
    }

    public function test_missing_attestation_blocks_invoice_recognition(): void
    {
        $id = $this->invoice();
        $this->expectException(ValidationException::class);
        (new InvoiceDebtRecognitionService)->confirm($id, 7, now()->toDateString(), null, false);
    }

    public function test_incomplete_or_replacement_invoice_is_blocked(): void
    {
        $id = $this->invoice(['type' => 'Hóa đơn thay thế', 'tax_code' => null]);
        $service = new InvoiceDebtRecognitionService;
        self::assertNotEmpty($service->preview($id)['issues']);
        $this->expectException(ValidationException::class);
        $service->confirm($id, 7, now()->toDateString(), null, true);
    }
}
