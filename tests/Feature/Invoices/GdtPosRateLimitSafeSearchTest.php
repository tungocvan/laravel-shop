<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtPosRateLimitSafeSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('invoices.gdt.cache_key', 'gdt-pos-rate-limit-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-rate-limit-test-token', 'fake-token', 600);
    }

    public function test_multi_month_pos_preview_fails_before_any_gdt_request(): void
    {
        Http::fake();
        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-01-01', '2026-08-30', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Expected date-range guard.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('một tháng', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_regular_preview_is_not_limited_to_one_month(): void
    {
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200)]);
        $result = app(GdtInvoiceService::class)->previewSource('2026-01-01', '2026-02-28', true, 'regular');
        $this->assertSame(0, $result['count']);
        Http::assertSentCount(2);
    }

    public function test_pos_purchase_preview_uses_observed_gdt_filter_and_returns_rows(): void
    {
        Http::fake(['*' => Http::response([
            'datas' => [['dummy' => 'safe-fixture']],
            'total' => 1,
        ], 200)]);

        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
        );

        $this->assertSame(1, $result['count']);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/sco-query/invoices/purchase')
                && ($query['sort'] ?? null) === 'tdlap:desc'
                && str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                && str_contains((string) ($query['search'] ?? ''), 'tdlap=ge=01/07/2026T00:00:00')
                && str_contains((string) ($query['search'] ?? ''), 'tdlap=le=31/07/2026T23:59:59');
        });
    }

    public function test_pos_http_429_stops_without_retry_and_shows_retry_after(): void
    {
        Http::fake(['*' => Http::response(['message' => 'rate limited'], 429, ['Retry-After' => '120'])]);
        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-09-01', '2026-09-30', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Expected HTTP 429.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 429', $exception->getMessage());
            $this->assertStringContainsString('120 giây', $exception->getMessage());
            $this->assertStringContainsString('Không tự động thử lại', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }
}
