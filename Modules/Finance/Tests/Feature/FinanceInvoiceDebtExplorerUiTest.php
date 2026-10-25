<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\InvoiceDebtExplorer;
use Tests\TestCase;

final class FinanceInvoiceDebtExplorerUiTest extends TestCase
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

    private function login(bool $finance, bool $invoices): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn($finance);
        $user->shouldReceive('can')->with('invoices-list')->andReturn($invoices);
        $user->shouldReceive('can')->with('finance.debt.manage')->andReturn(true);
        Auth::guard('admin')->setUser($user);
    }

    public function test_invoice_catalog_filters_and_existing_source_link_are_read_only(): void
    {
        $this->login(true, true);
        $tag = 'FIN-INVOICE-'.uniqid();
        $sold = DB::table('invoices')->insertGetId([
            'invoice_number' => $tag.'-S', 'symbol' => 'AA/26E',
            'invoice_type' => 'sold', 'name' => $tag, 'tax_code' => '1234567890',
            'total_amount' => '120000.00', 'issued_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'invoice_number' => $tag.'-P', 'invoice_type' => 'purchase',
            'name' => $tag, 'total_amount' => '80000.00',
            'issued_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_debt_obligations')->insert([
            'source_module' => 'Invoices', 'source_type' => 'invoice',
            'source_document_id' => (string) $sold, 'reference_number' => $tag.'-S',
            'direction' => 'receivable', 'status' => 'draft',
            'counterparty_name' => $tag, 'original_amount' => '120000.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::test(InvoiceDebtExplorer::class)->set('search', $tag)->assertOk()
            ->assertSee($tag.'-S')->assertSee($tag.'-P')
            ->assertSee('Đã ghi nhận')->assertSee('120.000 ₫');
        Livewire::test(InvoiceDebtExplorer::class)->set('search', $tag)
            ->set('invoiceType', 'sold')->assertSee($tag.'-S')->assertDontSee($tag.'-P');
        self::assertSame(1, DB::table('finance_debt_obligations')->where('source_document_id', (string) $sold)->count());
    }

    public function test_invoice_pagination_is_compact_and_filter_resets_invoice_page(): void
    {
        $this->login(true, true);
        $tag = 'PAGING-'.uniqid();
        $rows = [];
        for ($n = 1; $n <= 55; $n++) {
            $rows[] = [
                'invoice_number' => $tag.'-'.$n,
                'invoice_type' => 'sold',
                'name' => $tag,
                'total_amount' => '100.00',
                'issued_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('invoices')->insert($rows);

        Livewire::test(InvoiceDebtExplorer::class)
            ->set('search', $tag)
            ->assertSee('Trang 1 / 6')
            ->assertSee('Trang cuối')
            ->call('gotoPage', 4, 'invoicePage')
            ->assertSee('Trang 4 / 6')
            ->set('invoiceType', 'purchase')
            ->assertDontSee('Trang 4 / 6')
            ->assertSee('Không tìm thấy hóa đơn phù hợp.');
    }

    public function test_invoice_review_panel_opens_for_selected_invoice(): void
    {
        $this->login(true, true);
        $number = 'REVIEW-'.uniqid();
        $id = DB::table('invoices')->insertGetId([
            'invoice_number' => $number,
            'symbol' => 'AA/26E',
            'invoice_type' => 'purchase',
            'name' => 'Supplier review',
            'tax_code' => '0123456789',
            'total_amount' => '120000.00',
            'issued_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Livewire::test(InvoiceDebtExplorer::class)
            ->set('search', $number)
            ->call('openReview', $id)
            ->assertSet('reviewInvoiceId', $id)
            ->assertSet('dueOn', now()->addDays(90)->toDateString())
            ->assertSee('Kiểm tra trước khi xác nhận công nợ')
            ->assertSee('Supplier review')
            ->assertSee('Phải trả');
    }

    public function test_invoice_catalog_requires_both_module_permissions(): void
    {
        $this->login(true, false);
        Livewire::test(InvoiceDebtExplorer::class)->assertForbidden();
        $this->login(false, true);
        Livewire::test(InvoiceDebtExplorer::class)->assertForbidden();
    }

    public function test_disabled_invoice_module_displays_unavailable_message(): void
    {
        $this->login(true, true);
        config()->set('modules.registry.Invoices.enabled', false);
        Livewire::test(InvoiceDebtExplorer::class)->assertOk()->assertSee('chưa được bật');
    }
}
