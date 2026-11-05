<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtPosHttpDiagnosticsTest extends TestCase
{
    public function test_json_pos_http_failure_logs_only_allowlisted_metadata(): void
    {
        $this->assertPosFailureDiagnostics([
            'content_type' => 'application/json',
            'body' => '{"message":"SECRET_INVOICE_123","token":"SECRET_TOKEN"}',
            'format' => 'json',
        ]);
    }

    public function test_html_pos_http_failure_logs_only_allowlisted_metadata(): void
    {
        $this->assertPosFailureDiagnostics([
            'content_type' => 'text/html',
            'body' => '<html>SECRET_INVOICE_123 SECRET_TOKEN</html>',
            'format' => 'html',
        ]);
    }

    private function assertPosFailureDiagnostics(array $case): void
    {
            config()->set('invoices.gdt.cache_key', 'gdt-diagnostics-test-token');
            config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
            Cache::put('gdt-diagnostics-test-token', 'SECRET_TOKEN', 600);

            Http::fake(function ($request) use ($case) {
                if (str_contains($request->url(), '/sco-query/invoices/purchase')) {
                    return Http::response($case['body'], 500, ['Content-Type' => $case['content_type']]);
                }

                if (str_contains($request->url(), '/query/invoices/purchase')) {
                    return Http::response(['datas' => [], 'total' => 0], 200);
                }

                return Http::response([], 404);
            });

            // POS now logs safe cookie-jar lifecycle metadata before an HTTP failure.
            // Allow that independent diagnostic without weakening the HTTP 500 warning assertion.
            Log::shouldReceive('info')->once()->with(
                'GDT POS captcha cookie lifecycle.',
                Mockery::on(function ($context) {
                    $this->assertSame('request', $context['stage']);
                    $this->assertSame('purchase', $context['action']);
                    $this->assertSame(0, $context['stored_cookie_count']);
                    $this->assertSame(0, $context['jar_cookie_count']);
                    $this->assertFalse($context['has_cookie']);

                    return true;
                })
            );

            Log::shouldReceive('warning')->once()->with(
                'GDT invoice source HTTP failure.',
                Mockery::on(function ($context) use ($case) {
                    $this->assertSame([
                        'source' => 'pos',
                        'endpoint' => '/sco-query/invoices/purchase',
                        'action' => 'purchase',
                        'page' => 1,
                        'http_status' => 500,
                        'response_format' => $case['format'],
                        'search_has_pos_filter' => true,
                        'page_size' => 15,
                        'has_pagination_state' => false,
                    ], $context);

                    $encoded = json_encode($context);
                    $this->assertStringNotContainsString('SECRET_INVOICE_123', $encoded);
                    $this->assertStringNotContainsString('SECRET_TOKEN', $encoded);

                    return true;
                })
            );

            $thrown = null;
            try {
                app(GdtInvoiceService::class)->processRange(
                    '2026-11-01', '2026-11-02', null, true, 'all', uniqid('diagnostics-', true), ['ckey' => 'fake-key', 'cvalue' => 'fake-value']
                );
            } catch (\RuntimeException $exception) {
                $thrown = $exception;
            }
            $this->assertInstanceOf(\RuntimeException::class, $thrown, 'POS HTTP 500 must propagate.');
            $this->assertStringContainsString('HTTP 500', $thrown->getMessage());
            $this->assertStringContainsString('Không tạo file thiếu', $thrown->getMessage());

            // Verify the POS request profile without contacting GDT or exposing credentials.
            Http::assertSent(function ($request) {
                if (! str_contains($request->url(), '/sco-query/invoices/purchase')) {
                    return false;
                }

                $query = [];
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return ($query['sort'] ?? null) === 'tdlap:desc'
                    && (string) ($query['size'] ?? '') === '15'
                    && str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                    && ($query['ckey'] ?? '') === 'fake-key'
                    && ($query['cvalue'] ?? '') === 'fake-value'
                    && ! array_key_exists('state', $query)
                    && $request->hasHeader('Authorization')
                    && $request->hasHeader('Origin')
                    && $request->hasHeader('Referer')
                    && $request->hasHeader('request-id');
            });

            Http::assertSent(function ($request) {
                return str_contains($request->url(), '/sco-query/invoices/purchase')
                    && str_contains(urldecode($request->url()), 'ttxly==8');
            });

    }
}
