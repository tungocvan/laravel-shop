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
    public function test_pos_http_failure_logs_only_allowlisted_metadata_without_leaking_response(): void
    {
        foreach ([
            ['content_type' => 'application/json', 'body' => '{"message":"SECRET_INVOICE_123","token":"SECRET_TOKEN"}', 'format' => 'json'],
            ['content_type' => 'text/html', 'body' => '<html>SECRET_INVOICE_123 SECRET_TOKEN</html>', 'format' => 'html'],
        ] as $case) {
            config()->set('invoices.gdt.cache_key', 'gdt-diagnostics-test-token');
            config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
            Cache::put('gdt-diagnostics-test-token', 'SECRET_TOKEN', 600);

            Http::fake(function ($request) use ($case) {
                if (str_contains($request->url(), '/query/invoices/purchase')) {
                    return Http::response(['datas' => [], 'total' => 0], 200);
                }

                if (str_contains($request->url(), '/sco-query/invoices/purchase')) {
                    return Http::response($case['body'], 500, ['Content-Type' => $case['content_type']]);
                }

                return Http::response([], 404);
            });

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
                        'page_size' => 50,
                        'has_pagination_state' => false,
                    ], $context);

                    $encoded = json_encode($context);
                    $this->assertStringNotContainsString('SECRET_INVOICE_123', $encoded);
                    $this->assertStringNotContainsString('SECRET_TOKEN', $encoded);

                    return true;
                })
            );

            try {
                app(GdtInvoiceService::class)->processRange(
                    '2026-11-01', '2026-11-02', null, true, 'all', uniqid('diagnostics-', true)
                );
                $this->fail('POS HTTP 500 must propagate.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('HTTP 500', $exception->getMessage());
                $this->assertStringContainsString('Không tạo file thiếu', $exception->getMessage());
            }

            Http::assertSent(function ($request) {
                return str_contains($request->url(), '/sco-query/invoices/purchase')
                    && str_contains(urldecode($request->url()), 'ttxly==8');
            });

        }
    }
}
