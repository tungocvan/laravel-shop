<?php

namespace Tests\Feature\Invoices;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtPosRequestTransportContractTest extends TestCase
{
    public function test_pos_preview_ten_results_does_not_write_canonical(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-canonical-preview-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-canonical-preview-token', 'fake-token', 600);

        $rows = [];
        for ($number = 1; $number <= 10; $number++) {
            $rows[] = [
                'shdon' => (string) $number,
                'khmshdon' => '1',
                'khhdon' => 'C26TEST',
                'tdlap' => '2026-07-15T10:00:00',
            ];
        }
        Http::fake(['*' => Http::response(
            ['datas' => $rows, 'total' => 10],
            200,
            ['Content-Type' => 'application/json']
        )]);
        \Illuminate\Support\Facades\DB::shouldReceive('transaction')->never();

        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'fake-key', 'cvalue' => 'fake-value', 'cookies' => []]
        );

        $this->assertSame('pos', $result['source']);
        $this->assertSame(10, $result['count']);
        $this->assertCount(10, $result['sample']);
        Http::assertSentCount(1);
    }

    public function test_incomplete_pos_response_cannot_enter_canonical_transaction(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-canonical-incomplete-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-canonical-incomplete-token', 'fake-token', 600);

        Http::fake(['*' => Http::response(
            ['datas' => [['shdon' => '1', 'tdlap' => '2026-07-15T10:00:00']], 'total' => 10],
            200,
            ['Content-Type' => 'application/json']
        )]);
        \Illuminate\Support\Facades\DB::shouldReceive('transaction')->never();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không tạo file thiếu');
        app(GdtInvoiceService::class)->processRange(
            '2026-07-01', '2026-07-31', null, true, 'pos', null,
            ['ckey' => 'fake-key', 'cvalue' => 'fake-value', 'cookies' => []]
        );
    }

    public function test_pos_canonical_replay_preserves_identity_raw_link_and_regular_isolation(): void
    {
        // This regression runs only against an isolated in-memory SQLite database.
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver required for isolated canonical integration test.');
        }
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        \Illuminate\Support\Facades\DB::purge('sqlite');
        $schema = \Illuminate\Support\Facades\Schema::connection('sqlite');
        $schema->create('invoices', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->string('lookup_code')->nullable();
            $table->string('symbol')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('type')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('tax_rate', 10, 2)->nullable();
            $table->decimal('vat_amount', 18, 2)->nullable();
            $table->decimal('amount_before_vat', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();
            $table->string('invoice_type');
            $table->timestamps();
        });
        $schema->create('invoice_source_records', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('provider');
            $table->string('source_version')->nullable();
            $table->json('header_payload')->nullable();
            $table->string('header_hash')->nullable();
            $table->timestamp('header_fetched_at')->nullable();
            $table->string('business_classification')->nullable();
            $table->string('classification_scope')->nullable();
            $table->timestamps();
        });

        $rows = [];
        for ($number = 1; $number <= 10; $number++) {
            $rows[] = [
                'Mã tra cứu' => 'POS-'. $number,
                'Ký hiệu' => '1/C26TEST',
                'Số hóa đơn' => (string) $number,
                'Ngày lập' => '15/07/2026',
                'Mã số thuế' => '0100000000',
                'Thành tiền' => 1000,
                '_gdt_raw_payload' => ['id' => 'fixture-'. $number, 'shdon' => (string) $number],
            ];
        }
        $service = app(GdtInvoiceService::class);
        $persist = new \ReflectionMethod(GdtInvoiceService::class, 'persistInvoices');

        $first = $persist->invoke($service, $rows, true);
        $this->assertSame(10, $first['created']);
        $this->assertCount(10, $first['invoice_ids']);

        $replay = $persist->invoke($service, $rows, true);
        $this->assertSame(0, $replay['created']);
        $this->assertSame(10, $replay['unchanged']);
        $this->assertSame($first['invoice_ids'], $replay['invoice_ids']);
        $this->assertSame(10, \Modules\Invoices\Models\Invoices::query()->where('invoice_type', 'purchase')->count());
        $this->assertSame(10, \Modules\Invoices\Models\InvoiceSourceRecord::query()->where('provider', 'gdt')->count());

        $rows[0]['Thành tiền'] = 1200;
        $changed = $persist->invoke($service, $rows, true);
        $this->assertSame(0, $changed['created']);
        $this->assertSame(1, $changed['updated']);
        $this->assertSame(9, $changed['unchanged']);

        $regular = $persist->invoke($service, [$rows[0]], false);
        $this->assertSame(1, $regular['created']);
        $this->assertSame(10, \Modules\Invoices\Models\Invoices::query()->where('invoice_type', 'purchase')->count());
        $this->assertSame(1, \Modules\Invoices\Models\Invoices::query()->where('invoice_type', 'sold')->count());
    }

    public function test_pos_october_replay_preserves_september_invoice_date_boundary(): void
    {
        // POS responses may include an invoice issued in the preceding month.
        // Keep the source-issued date; do not coerce it to the query month.
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver required for isolated canonical integration test.');
        }

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        \Illuminate\Support\Facades\DB::purge('sqlite');

        $schema = \Illuminate\Support\Facades\Schema::connection('sqlite');
        $schema->create('invoices', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->string('lookup_code')->nullable();
            $table->string('symbol')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('type')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('tax_rate', 10, 2)->nullable();
            $table->decimal('vat_amount', 18, 2)->nullable();
            $table->decimal('amount_before_vat', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();
            $table->string('invoice_type');
            $table->timestamps();
        });
        $schema->create('invoice_source_records', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('provider');
            $table->string('source_version')->nullable();
            $table->json('header_payload')->nullable();
            $table->string('header_hash')->nullable();
            $table->timestamp('header_fetched_at')->nullable();
            $table->string('business_classification')->nullable();
            $table->string('classification_scope')->nullable();
            $table->timestamps();
        });

        $rows = [
            [
                'Mã tra cứu' => 'POS-OCT-898',
                'Ký hiệu' => '1/C26MDC',
                'Số hóa đơn' => '898',
                'Ngày lập' => '26/10/2026',
                'Mã số thuế' => '0100000000',
                'Thành tiền' => 1000,
                '_gdt_raw_payload' => ['id' => 'oct-898', 'shdon' => '898'],
            ],
            [
                'Mã tra cứu' => 'POS-SEP-1017685',
                'Ký hiệu' => '1/C26MDV',
                'Số hóa đơn' => '1017685',
                'Ngày lập' => '30/09/2026',
                'Mã số thuế' => '0100000000',
                'Thành tiền' => 2000,
                '_gdt_raw_payload' => ['id' => 'sep-1017685', 'shdon' => '1017685'],
            ],
        ];

        $persist = new \ReflectionMethod(GdtInvoiceService::class, 'persistInvoices');
        $service = app(GdtInvoiceService::class);

        $first = $persist->invoke($service, $rows, true);
        $this->assertSame(2, $first['created']);
        $this->assertSame(1, \Modules\Invoices\Models\Invoices::query()
            ->where('invoice_type', 'purchase')
            ->whereBetween('issued_date', ['2026-10-01', '2026-10-31'])->count());
        $this->assertSame(1, \Modules\Invoices\Models\Invoices::query()
            ->where('invoice_type', 'purchase')
            ->whereBetween('issued_date', ['2026-09-01', '2026-09-30'])->count());

        $replay = $persist->invoke($service, $rows, true);
        $this->assertSame(0, $replay['created']);
        $this->assertSame(2, $replay['unchanged']);
        $this->assertSame($first['invoice_ids'], $replay['invoice_ids']);
        $this->assertSame(2, \Modules\Invoices\Models\InvoiceSourceRecord::query()
            ->where('provider', 'gdt')->count());
    }

    public function test_cookie_snapshot_is_eligible_for_pos_endpoint_without_network(): void
    {
        $jar = new CookieJar;
        $jar->setCookie(new SetCookie([
            'Name' => 'session-test',
            'Value' => 'SECRET_COOKIE',
            'Domain' => 'gdt-test.invalid',
            'Path' => '/',
            'Secure' => true,
        ]));

        $eligible = $jar->withCookieHeader(new Request('GET', 'https://gdt-test.invalid/api/sco-query/invoices/purchase'));
        $otherHost = $jar->withCookieHeader(new Request('GET', 'https://unrelated.invalid/api/sco-query/invoices/purchase'));

        $this->assertTrue($eligible->hasHeader('Cookie'));
        $this->assertFalse($otherHost->hasHeader('Cookie'));
    }

    public function test_pos_request_contract_and_html_error_fail_closed_without_network(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-transport-contract-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        config()->set('invoices.gdt.user_agent', 'GDT-Transport-Contract-Test/1.0');
        Cache::put('gdt-pos-transport-contract-token', 'SECRET_TOKEN', 600);

        Log::spy();
        Http::fake(['*' => Http::response(
            '<!doctype html><html><head><title>ERROR</title></head><body>SECRET_INVOICE</body></html>',
            200,
            ['Content-Type' => 'text/html']
        )]);

        $captcha = [
            'ckey' => 'SECRET_CKEY',
            'cvalue' => 'SECRET_CVALUE',
            'cookies' => [[
                'Name' => 'session-test',
                'Value' => 'SECRET_COOKIE',
                'Domain' => 'gdt-test.invalid',
                'Path' => '/',
                'Secure' => true,
            ]],
        ];

        try {
            app(GdtInvoiceService::class)->processRange(
                '2026-07-01', '2026-07-31', null, true, 'pos', null, $captcha
            );
            $this->fail('HTTP 200 HTML must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Không coi là 0 hóa đơn', $exception->getMessage());
        }

        // A single compound assertSent hides which field differs. Inspect the fake
        // request in isolated assertions, without printing token/CAPTCHA/cookie values.
        $requests = Http::recorded(fn ($request) => str_contains(
            $request->url(), '/sco-query/invoices/purchase'
        ));
        $this->assertCount(1, $requests, 'Expected exactly one fake POS purchase request.');
        $request = $requests->first()[0];

        $query = [];
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $this->assertSame('tdlap:desc', $query['sort'] ?? null);
        $this->assertSame('15', (string) ($query['size'] ?? ''));
        $this->assertSame(
            'tdlap=ge=01/07/2026T00:00:00;tdlap=le=31/07/2026T23:59:59;ttxly==8',
            $query['search'] ?? null
        );
        $this->assertSame('SECRET_CKEY', $query['ckey'] ?? null);
        $this->assertSame('SECRET_CVALUE', $query['cvalue'] ?? null);
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertSame('GDT-Transport-Contract-Test/1.0', $request->header('User-Agent')[0] ?? null);
        $this->assertSame('vi', $request->header('Accept-Language')[0] ?? null);
        $this->assertSame('/tra-cuu/tra-cuu-hoa-don', $request->header('End-Point')[0] ?? null);
        $this->assertTrue($request->hasHeader('ckey'), 'Missing ckey request header.');
        $this->assertTrue($request->hasHeader('cvalue'), 'Missing cvalue request header.');
        $this->assertSame(
            rawurlencode('Tìm kiếm (hóa đơn máy tính tiền mua vào)'),
            $request->header('Action')[0] ?? null
        );
        $this->assertSame(
            'https://gdt-test.invalid/tra-cuu/tra-cuu-hoa-don',
            $request->header('Referer')[0] ?? null
        );
        $this->assertTrue($request->hasHeader('request-id'));

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS invoice query response metadata.') {
                return false;
            }

            $this->assertSame('html', $context['response_format']);
            $this->assertSame(200, $context['http_status']);
            $this->assertSame(0, $context['redirect_count']);
            $encoded = json_encode($context);
            foreach (['SECRET_TOKEN', 'SECRET_COOKIE', 'SECRET_CKEY', 'SECRET_CVALUE', 'SECRET_INVOICE'] as $secret) {
                $this->assertStringNotContainsString($secret, $encoded);
            }

            return true;
        })->once();
    }
}
