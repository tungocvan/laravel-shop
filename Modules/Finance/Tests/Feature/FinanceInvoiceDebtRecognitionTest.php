<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\InvoiceDebtRecognitionService;
use Modules\Finance\Livewire\InvoiceDebtExplorer;
use Livewire\Livewire;
use Mockery;
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


    public function test_bulk_selection_preview_and_individual_results(): void
    {
        $user = Mockery::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $user->shouldReceive('can')->with('finance.view')->andReturn(true);
        $user->shouldReceive('can')->with('invoices-list')->andReturn(true);
        $user->shouldReceive('can')->with('finance.debt.manage')->andReturn(true);
        $user->shouldReceive('getAuthIdentifier')->andReturn(7);
        auth('admin')->setUser($user);

        $good = $this->invoice();
        $bad = $this->invoice(['type' => 'Hóa đơn thay thế']);
        $screen = Livewire::test(InvoiceDebtExplorer::class)
            ->call('toggleBulkPage')
            ->assertSet('bulkSelected', [$bad, $good])
            ->call('prepareBulk')
            ->assertSee('Xem trước ghi nhận công nợ')
            ->assertSee('Hóa đơn có dấu hiệu hủy/thay thế/điều chỉnh.')
            ->assertSet('bulkDueDates.'.$good, now()->addDays(90)->toDateString())
            ->set('bulkDueDates.'.$good, now()->addDays(100)->toDateString())
            ->set('bulkReviewed', true)
            ->call('confirmBulk')
            ->assertSee('Kết quả xác nhận 2 hóa đơn')
            ->assertSet('bulkResults.0.status', 'skipped')
            ->assertSet('bulkResults.1.status', 'success');
        self::assertSame(now()->addDays(100)->toDateString(), DB::table('finance_debt_obligations')->where('source_module', 'Invoices')->where('source_document_id', (string) $good)->value('due_on'));
        self::assertSame(1, DB::table('finance_debt_obligations')->where('source_module', 'Invoices')
            ->where('source_type', 'invoice')->where('source_document_id', (string) $good)->count());
        self::assertSame(0, DB::table('finance_debt_obligations')->where('source_module', 'Invoices')
            ->where('source_type', 'invoice')->where('source_document_id', (string) $bad)->count());
    }


    public function test_default_due_date_is_90_calendar_days_including_leap_year(): void
    {
        $service = new InvoiceDebtRecognitionService;
        self::assertSame('2027-01-10', $service->defaultDueOn('2026-10-12'));
        self::assertSame('2024-03-31', $service->defaultDueOn('2024-01-01'));
        self::assertSame('2024-05-30', $service->defaultDueOn('2024-03-01'));
        self::assertNull($service->defaultDueOn('2026-02-30'));
    }

    public function test_invoice_due_date_can_precede_recognition_date_when_invoice_is_old(): void
    {
        $id = $this->invoice(['issued_date' => '2025-01-01']);
        $due = (new InvoiceDebtRecognitionService)->defaultDueOn('2025-01-01');
        $debtId = (new InvoiceDebtRecognitionService)->confirm($id, 7, '2026-10-25', $due, true);
        self::assertSame($due, DB::table('finance_debt_obligations')->find($debtId)->due_on);
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
