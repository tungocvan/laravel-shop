<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtSourceRetryHttpIntegrationTest extends TestCase
{
    public function test_pos_http_500_retries_without_refetching_completed_empty_regular_source(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-test-token', 'fake-token', 600);

        $regularCalls = 0;
        $posCalls = 0;
        Http::fake(function ($request) use (&$regularCalls, &$posCalls) {
            if (str_contains($request->url(), '/sco-query/invoices/purchase')) {
                $posCalls++;

                return Http::response($posCalls === 1 ? ['error' => 'temporary'] : ['datas' => [], 'total' => 0], $posCalls === 1 ? 500 : 200);
            }

            if (str_contains($request->url(), '/query/invoices/purchase')) {
                $regularCalls++;

                return Http::response(['datas' => [], 'total' => 0], 200);
            }

            return Http::response(['error' => 'unexpected request'], 404);
        });

        $service = app(GdtInvoiceService::class);
        $syncId = 'integration-'.uniqid('', true);

        try {
            $service->processRange('2026-11-01', '2026-11-02', null, true, 'all', $syncId);
            $this->fail('Expected POS HTTP 500 to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 500', $exception->getMessage());
        }

        $this->assertSame(1, $regularCalls);
        $this->assertSame(1, $posCalls);

        $result = $service->processRange('2026-11-01', '2026-11-02', null, true, 'all', $syncId);
        $this->assertNull($result);
        $this->assertSame(1, $regularCalls, 'Regular must not be refetched after its full-month checkpoint.');
        $this->assertSame(2, $posCalls, 'POS must be retried.');

        Http::assertSentCount(3);
    }
}
