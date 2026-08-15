<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Livewire\GdtInvoice;
use Modules\Invoices\Livewire\HoadonList;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\InvoiceImportExportService;
use Modules\Invoices\Services\InvoiceService;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class InvoicesModuleTest extends TestCase
{
    public function test_invoices_module_is_enabled(): void
    {
        $this->assertTrue((bool) config('invoices.module.enabled'));
    }

    public function test_captcha_and_login_keep_token_only_in_server_cache(): void
    {
        config([
            'invoices.gdt.base_url' => 'https://hoadondientu.gdt.gov.vn/api',
            'invoices.gdt.username' => 'configured-at-runtime',
            'invoices.gdt.password' => 'configured-at-runtime',
            'invoices.gdt.cache_key' => 'test-gdt-token',
        ]);

        Http::fake([
            '*/captcha' => Http::response(['key' => 'captcha-key', 'content' => '<svg></svg>']),
            '*/security-taxpayer/authenticate' => Http::response(['token' => 'server-only-token']),
        ]);

        $service = app(GdtApiService::class);

        $this->assertSame('captcha-key', $service->loadCaptcha()['key']);
        $result = $service->login('captcha-value', 'captcha-key', 600);

        $this->assertSame('success', $result['status']);
        $this->assertArrayNotHasKey('token', $result);
        $this->assertSame('server-only-token', Cache::get('test-gdt-token'));
    }

    public function test_invoice_search_uses_cursor_without_page_for_sold_and_purchase(): void
    {
        config([
            'invoices.gdt.base_url' => 'https://hoadondientu.gdt.gov.vn/api',
            'invoices.gdt.cache_key' => 'test-gdt-token',
        ]);
        Cache::put('test-gdt-token', 'server-only-token', 600);

        Http::fakeSequence()
            ->push(['datas' => [['shdon' => '1']], 'total' => 2, 'state' => 'next-cursor'])
            ->push(['datas' => [['shdon' => '2']], 'total' => 2])
            ->push(['datas' => [['shdon' => '1']], 'total' => 2, 'state' => 'next-cursor'])
            ->push(['datas' => [['shdon' => '2']], 'total' => 2]);

        foreach (['sold', 'purchase'] as $type) {
            Livewire::test(GdtInvoice::class)
                ->set('fromDate', '2026-07-01')
                ->set('toDate', '2026-07-31')
                ->set('invoiceType', $type)
                ->call('searchInvoices')
                ->assertSet('total', 2)
                ->assertCount('invoices', 2);

            Http::assertSent(function ($request) use ($type) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return str_contains($request->url(), "/query/invoices/{$type}")
                    && ($query['sort'] ?? null) === 'tdlap:desc'
                    && ! array_key_exists('page', $query);
            });

            Http::assertSent(function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return ($query['state'] ?? null) === 'next-cursor'
                    && ! array_key_exists('page', $query);
            });
        }
    }

    public function test_queue_command_dispatches_invoice_type(): void
    {
        Bus::fake();

        $this->artisan('gdt:invoices', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            '--queue' => true,
            '--vatIn' => true,
        ])->assertSuccessful();

        Bus::assertDispatched(ProcessGdtInvoicesJob::class, fn ($job) => $job->vatIn === true
            && $job->start === '2026-07-01'
            && $job->end === '2026-07-31');
    }

    public function test_excel_import_supports_sold_and_purchase(): void
    {
        $this->withInvoicesTable(function () {
            $file = storage_path('app/invoices-module-test.xlsx');
            (new FastExcel([[
                'Mã tra cứu' => 'test-lookup',
                'Ký hiệu' => '1/C26T',
                'Số hóa đơn' => '999999',
                'Loại hóa đơn' => 'Hóa đơn GTGT',
                'Ngày lập' => '31/07/2026',
                'Mã số thuế' => 'test-tax-code',
                'Đơn vị' => 'Test',
                'Địa chỉ' => '',
                'Email' => '',
                'Phone' => '',
                'Thuế suất' => '10',
                'Tiền VAT' => '100',
                'Trước VAT' => '1000',
                'Thành tiền' => '1100',
            ]]))->export($file);

            try {
                $this->artisan('gdt:import-excel', ['file' => $file, '--type' => 'sold'])->assertSuccessful();
                $this->assertDatabaseHas('invoices', ['lookup_code' => 'test-lookup', 'invoice_type' => 'sold']);

                DB::table('invoices')->where('lookup_code', 'test-lookup')->delete();

                $this->artisan('gdt:import-excel', ['file' => $file, '--type' => 'purchase'])->assertSuccessful();
                $this->assertDatabaseHas('invoices', ['lookup_code' => 'test-lookup', 'invoice_type' => 'purchase']);
            } finally {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });
    }

    public function test_shared_invoice_import_skips_duplicate_and_preserves_decimal_strings(): void
    {
        $this->withInvoicesTable(function () {
            $file = storage_path('app/invoices-shared-import-test.xlsx');
            (new FastExcel([[
                'Mã tra cứu' => 'shared-lookup',
                'Ký hiệu' => '1/C26T',
                'Số hóa đơn' => '123456',
                'Loại' => 'Hóa đơn GTGT',
                'Ngày lập' => '15/08/2026',
                'Mã số thuế' => 'shared-tax',
                'Đơn vị' => 'Shared Test',
                'Thuế suất' => '10',
                'Tiền VAT' => '1.234,56',
                'Trước VAT' => '12.345,67',
                'Thành tiền' => '13.580,23',
                'Loại hóa đơn' => 'sold',
            ]]))->export($file);

            try {
                $service = app(InvoiceImportExportService::class);
                $first = $service->importForType($file, 'sold');
                $second = $service->importForType($file, 'sold');

                $this->assertSame(1, $first['success_rows']);
                $this->assertSame(0, $second['success_rows']);
                $this->assertSame(1, $second['skipped_rows']);
                $this->assertSame(1, DB::table('invoices')->where('lookup_code', 'shared-lookup')->count());
                $this->assertSame('1234.56', Invoices::query()->where('lookup_code', 'shared-lookup')->value('vat_amount'));
            } finally {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });
    }

    public function test_duplicate_identity_does_not_overwrite_existing_invoice_data(): void
    {
        $this->withInvoicesTable(function () {
            $firstFile = storage_path('app/invoices-idempotent-first.xlsx');
            $secondFile = storage_path('app/invoices-idempotent-second.xlsx');

            $base = [
                'Mã tra cứu' => 'identity-lookup',
                'Ký hiệu' => '1/C26T',
                'Số hóa đơn' => 'ID-001',
                'Loại' => 'Hóa đơn GTGT',
                'Ngày lập' => '15/08/2026',
                'Mã số thuế' => 'identity-tax',
                'Thuế suất' => '10',
                'Loại hóa đơn' => 'sold',
            ];

            (new FastExcel([[...$base, 'Đơn vị' => 'Original Name', 'Thành tiền' => '1100']]))->export($firstFile);
            (new FastExcel([[...$base, 'Đơn vị' => 'Changed Name', 'Thành tiền' => '9999']]))->export($secondFile);

            try {
                $service = app(InvoiceImportExportService::class);
                $first = $service->importForType($firstFile, 'sold');
                $second = $service->importForType($secondFile, 'sold');

                $this->assertSame(1, $first['success_rows']);
                $this->assertSame(0, $second['success_rows']);
                $this->assertSame(1, $second['skipped_rows']);
                $this->assertSame(1, DB::table('invoices')->where('lookup_code', 'identity-lookup')->count());

                $invoice = Invoices::query()->where('lookup_code', 'identity-lookup')->firstOrFail();
                $this->assertSame('Original Name', $invoice->name);
                $this->assertSame('1100.00', $invoice->total_amount);
            } finally {
                @unlink($firstFile);
                @unlink($secondFile);
            }
        });
    }

    public function test_statistics_are_aggregated_in_one_query_and_preserve_filter_semantics(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('stats-5', 'sold', '5', '100.00', '5.00', '2026-08-15', 'Customer A'),
                $this->invoiceRow('stats-8', 'sold', '8', '200.00', '16.00', '2026-08-15', 'Customer B'),
                $this->invoiceRow('stats-10', 'purchase', '10', '300.00', '30.00', '2026-08-15', 'Customer C'),
                $this->invoiceRow('stats-other', 'sold', '7', '400.00', '28.00', '2026-08-15', 'Customer D'),
            ]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $stats = app(InvoiceService::class)->statistics([
                'invoice_type' => 'sold',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
                'tax_rate' => 'all',
            ]);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertCount(1, $queries);
            $this->assertSame(3, $stats['count']);
            $this->assertEquals(700.0, (float) $stats['total_amount']);
            $this->assertEquals(49.0, (float) $stats['vat_amount']);
            $this->assertEquals(100.0, (float) $stats['by_tax_rate'][5]);
            $this->assertEquals(200.0, (float) $stats['by_tax_rate'][8]);
            $this->assertEquals(0.0, (float) $stats['by_tax_rate'][10]);
            $this->assertEquals(400.0, (float) $stats['by_tax_rate']['other']);
        });
    }

    public function test_dashboard_uses_two_queries_and_preserves_yearly_totals(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('dash-sold-2026-a', 'sold', '10', '100.00', '10.00', '2026-08-15', 'Customer A'),
                $this->invoiceRow('dash-sold-2026-b', 'sold', '10', '200.00', '20.00', '2026-07-15', 'Customer A'),
                $this->invoiceRow('dash-purchase-2026', 'purchase', '8', '300.00', '24.00', '2026-06-15', 'Vendor A'),
                $this->invoiceRow('dash-sold-2025', 'sold', '5', '400.00', '20.00', '2025-06-15', 'Customer B'),
            ]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $dashboard = app(InvoiceService::class)->dashboard();

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertCount(2, $queries);
            $this->assertEquals(700.0, (float) $dashboard['sold_amount']);
            $this->assertEquals(300.0, (float) $dashboard['purchase_amount']);
            $this->assertSame(2, $dashboard['sold_customers']);
            $this->assertSame(1, $dashboard['purchase_customers']);
            $this->assertSame(2026, (int) $dashboard['yearly'][0]['year']);
            $this->assertEquals(300.0, (float) $dashboard['yearly'][0]['sold_total']);
            $this->assertEquals(300.0, (float) $dashboard['yearly'][0]['purchase_total']);
            $this->assertSame(2025, (int) $dashboard['yearly'][1]['year']);
            $this->assertEquals(400.0, (float) $dashboard['yearly'][1]['sold_total']);
        });
    }

    public function test_invoice_export_honors_filters_and_selected_ids(): void
    {
        $this->withInvoicesTable(function () {
            $firstId = DB::table('invoices')->insertGetId($this->invoiceRow('export-a', 'sold', '10'));
            $secondId = DB::table('invoices')->insertGetId($this->invoiceRow('export-b', 'purchase', '8'));

            $service = app(InvoiceImportExportService::class);
            $filteredPath = $service->export(['invoice_type' => 'sold']);
            $selectedPath = $service->export(['invoice_type' => 'sold', 'selected_ids' => [$secondId, $secondId, -1, 'bad']]);

            try {
                $filtered = (new FastExcel)->import(storage_path('app/public/'.$filteredPath));
                $selected = (new FastExcel)->import(storage_path('app/public/'.$selectedPath));

                $this->assertCount(1, $filtered);
                $this->assertSame('export-a', (string) $filtered->first()['Mã tra cứu']);
                $this->assertCount(1, $selected);
                $this->assertSame('export-b', (string) $selected->first()['Mã tra cứu']);
                $this->assertNotSame($firstId, $secondId);
            } finally {
                @unlink(storage_path('app/public/'.$filteredPath));
                @unlink(storage_path('app/public/'.$selectedPath));
            }
        });
    }

    private function withInvoicesTable(callable $callback): void
    {
        Schema::dropIfExists('invoices');
        $migration = require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php');
        $migration->up();

        try {
            $callback();
        } finally {
            DB::disableQueryLog();
            Schema::dropIfExists('invoices');
        }
    }

    private function invoiceRow(
        string $lookupCode,
        string $type,
        string $taxRate,
        string $totalAmount = '1100.00',
        string $vatAmount = '100.00',
        string $issuedDate = '2026-08-15',
        ?string $name = null
    ): array {
        return [
            'lookup_code' => $lookupCode,
            'symbol' => '1/C26T',
            'invoice_number' => $lookupCode,
            'type' => 'Hóa đơn GTGT',
            'issued_date' => $issuedDate,
            'tax_code' => 'tax-'.$lookupCode,
            'name' => $name ?? 'Test '.$lookupCode,
            'address' => '',
            'email' => '',
            'phone' => '',
            'tax_rate' => $taxRate,
            'vat_amount' => $vatAmount,
            'amount_before_vat' => '1000.00',
            'total_amount' => $totalAmount,
            'invoice_type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
