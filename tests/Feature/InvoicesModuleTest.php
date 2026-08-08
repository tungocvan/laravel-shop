<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Livewire\GdtInvoice;
use Modules\Invoices\Livewire\HoadonList;
use Modules\Invoices\Services\GdtApiService;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class InvoicesModuleTest extends TestCase
{
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

        DB::beginTransaction();

        try {
            $this->artisan('gdt:import-excel', [
                'file' => $file,
                '--type' => 'sold',
            ])->assertSuccessful();
            $this->assertDatabaseHas('invoices', ['lookup_code' => 'test-lookup', 'invoice_type' => 'sold']);

            DB::table('invoices')->where('lookup_code', 'test-lookup')->delete();

            $this->artisan('gdt:import-excel', [
                'file' => $file,
                '--type' => 'purchase',
            ])->assertSuccessful();
            $this->assertDatabaseHas('invoices', ['lookup_code' => 'test-lookup', 'invoice_type' => 'purchase']);

            Livewire::test(HoadonList::class)
                ->set('type', 'purchase')
                ->assertSee('999999')
                ->assertSee('Test');
        } finally {
            DB::rollBack();

            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
