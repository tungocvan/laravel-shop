<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                && (string) ($query['size'] ?? '') === '15'
                && str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                && str_contains((string) ($query['search'] ?? ''), 'tdlap=ge=01/07/2026T00:00:00')
                && str_contains((string) ($query['search'] ?? ''), 'tdlap=le=31/07/2026T23:59:59');
        });
    }

    public function test_pos_html_diagnostics_use_only_allowlisted_header_presence(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(
            '<html>SECRET_HTML_BODY SECRET_TOKEN</html>',
            200,
            [
                'Content-Type' => 'text/html',
                'Set-Cookie' => 'session=SECRET_COOKIE',
                'Cache-Control' => 'no-store',
                'Content-Length' => '37',
                'X-Private-Token' => 'SECRET_HEADER',
            ]
        )]);

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-07-01', '2026-07-31', true, 'pos',
                ['ckey' => 'SECRET_KEY', 'cvalue' => 'SECRET_ANSWER'],
            );
            $this->fail('Expected fail-closed POS HTML rejection.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cấu trúc danh sách POS không hợp lệ', $exception->getMessage());
        }

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS invoice query response metadata.') {
                return false;
            }

            $this->assertSame('HTML_UNCLASSIFIED', $context['html_response_kind']);
            $this->assertSame('NO_SIMPLE_TITLE', $context['html_fingerprint']['title_class']);
            $this->assertSame('UNDER_1KB', $context['html_fingerprint']['body_size_band']);
            $this->assertTrue($context['html_fingerprint']['has_html_tag']);
            $this->assertSame('UNSPECIFIED', $context['html_fingerprint']['server_class']);
            $this->assertTrue($context['response_has_set_cookie']);
            $this->assertFalse($context['response_has_www_authenticate']);
            $this->assertTrue($context['response_has_cache_control']);
            $this->assertTrue($context['response_has_content_length']);
            $serialized = json_encode($context);
            foreach (['SECRET_HTML_BODY', 'SECRET_TOKEN', 'SECRET_COOKIE', 'SECRET_HEADER', 'SECRET_KEY', 'SECRET_ANSWER'] as $secret) {
                $this->assertStringNotContainsString($secret, $serialized);
            }

            return true;
        })->once();
    }

    public function test_pos_search_headers_match_observed_browser_context_without_logging_captcha(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200)]);

        app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'SECRET_KEY', 'cvalue' => 'SECRET_ANSWER'],
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader('Referer', 'https://gdt-test.invalid/tra-cuu/tra-cuu-hoa-don')
                && $request->hasHeader('End-Point', '/tra-cuu/tra-cuu-hoa-don')
                && $request->hasHeader('Action', rawurlencode('Tìm kiếm (hóa đơn máy tính tiền mua vào)'))
                && $request->hasHeader('ckey', 'SECRET_KEY')
                && $request->hasHeader('cvalue', 'SECRET_ANSWER')
                && $request->hasHeader('Accept-Language', 'vi');
        });

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS request metadata.') {
                return false;
            }

            $encoded = json_encode($context);
            $this->assertStringNotContainsString('SECRET_KEY', $encoded);
            $this->assertStringNotContainsString('SECRET_ANSWER', $encoded);

            return true;
        })->once();
    }

    public function test_pos_uses_captcha_client_user_agent_and_reports_cookie_eligibility_only(): void
    {
        config()->set('invoices.gdt.user_agent', 'GDT-DIAGNOSTIC-AGENT');
        Log::spy();
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200)]);

        app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'SECRET_KEY', 'cvalue' => 'SECRET_ANSWER', 'cookies' => [
                ['Name' => 'session', 'Value' => 'SECRET_COOKIE', 'Domain' => 'gdt-test.invalid', 'Path' => '/', 'Secure' => true],
            ]],
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent', 'GDT-DIAGNOSTIC-AGENT');
        });
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS request metadata.') {
                return false;
            }
            $this->assertSame('purchase', $context['action']);
            $this->assertTrue($context['cookie_eligible_for_endpoint']);
            $this->assertTrue($context['user_agent_matches_captcha_client']);
            $encoded = json_encode($context);
            foreach (['SECRET_COOKIE', 'SECRET_KEY', 'SECRET_ANSWER'] as $secret) {
                $this->assertStringNotContainsString($secret, $encoded);
            }
            return true;
        })->once();
    }

    public function test_pos_http_200_logs_only_safe_response_counts(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response([
            'datas' => [['nbmst' => 'SENSITIVE_INVOICE_FIXTURE']],
            'total' => 1,
            'state' => null,
        ], 200, ['Content-Type' => 'application/json'])]);

        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'SECRET_CAPTCHA_KEY', 'cvalue' => 'SECRET_CAPTCHA_ANSWER'],
        );

        $this->assertSame(1, $result['count']);
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS invoice query response metadata.') {
                return false;
            }

            $this->assertSame('pos', $context['source']);
            $this->assertSame('purchase', $context['action']);
            $this->assertSame(200, $context['http_status']);
            $this->assertSame('object', $context['response_shape']);
            $this->assertSame('array', $context['datas_type']);
            $this->assertSame('integer', $context['total_type']);
            $this->assertSame('application/json', $context['content_type']);
            $this->assertSame('purchase', $context['request_action']);
            $this->assertSame(1, $context['response_total']);
            $this->assertSame(1, $context['response_datas_count']);
            $this->assertTrue($context['response_datas_is_array']);
            $this->assertTrue($context['search_has_pos_filter']);
            $serialized = json_encode($context);
            $this->assertStringNotContainsString('SENSITIVE_INVOICE_FIXTURE', $serialized);
            $this->assertStringNotContainsString('SECRET_CAPTCHA_KEY', $serialized);
            $this->assertStringNotContainsString('SECRET_CAPTCHA_ANSWER', $serialized);

            return true;
        })->once();
    }

    public function test_pos_purchase_request_and_response_share_safe_trace_id(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response('<html>SECRET_BODY</html>', 200, [
            'Content-Type' => 'text/html',
            'Location' => 'https://gdt-test.invalid/login?token=SECRET_QUERY',
        ])]);

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-07-01', '2026-07-31', true, 'pos',
                ['ckey' => 'SECRET_KEY', 'cvalue' => 'SECRET_ANSWER'],
            );
            $this->fail('Expected POS schema rejection.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cấu trúc danh sách POS không hợp lệ', $exception->getMessage());
        }

        $traceIds = [];
        foreach (['GDT POS request metadata.', 'GDT POS invoice query response metadata.'] as $event) {
            Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($event, &$traceIds) {
                if ($message !== $event) {
                    return false;
                }
                $this->assertSame('test', $context['runtime']);
                $this->assertSame('purchase', $context['action']);
                $this->assertMatchesRegularExpression(
                    '/^[0-9a-f]{8}-[0-9a-f-]{27,}$/i',
                    $context['trace_id'],
                );
                $traceIds[$event] = $context['trace_id'];
                $encoded = json_encode($context);
                foreach (['SECRET_BODY', 'SECRET_QUERY', 'SECRET_KEY', 'SECRET_ANSWER'] as $secret) {
                    $this->assertStringNotContainsString($secret, $encoded);
                }
                if ($event === 'GDT POS invoice query response metadata.') {
                    $this->assertSame('/login', $context['redirect_location_path']);
                    $this->assertSame('html', $context['response_format']);
                    $this->assertSame(0, $context['redirect_count']);
                    $this->assertSame('HTML_DIRECT_OR_UNTRACKED', $context['html_response_class']);
                }

                return true;
            })->once();
        }
        $this->assertCount(2, $traceIds);
        $this->assertSame(
            $traceIds['GDT POS request metadata.'],
            $traceIds['GDT POS invoice query response metadata.'],
        );
        Http::assertSentCount(1);
    }

    public function test_pos_html_response_records_only_redirect_count_not_history_urls(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response('<html>SECRET_HTML</html>', 200, [
            'Content-Type' => 'text/html',
            'X-Guzzle-Redirect-History' => 'https://gdt-test.invalid/login?secret=ONE, https://gdt-test.invalid/verify?secret=TWO',
        ])]);

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-07-01', '2026-07-31', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Expected POS schema rejection.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cấu trúc danh sách POS không hợp lệ', $exception->getMessage());
        }

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS invoice query response metadata.') {
                return false;
            }
            $this->assertSame(2, $context['redirect_count']);
            $this->assertSame('HTML_AFTER_REDIRECT', $context['html_response_class']);
            $encoded = json_encode($context);
            foreach (['SECRET_HTML', 'secret=ONE', 'secret=TWO'] as $secret) {
                $this->assertStringNotContainsString($secret, $encoded);
            }

            return true;
        })->once();
    }

    public function test_pos_http_200_empty_response_is_distinguishable_from_invalid_schema(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200, ['Content-Type' => 'application/json'])]);
        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
        );
        $this->assertSame(0, $result['count']);
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) =>
            $message === 'GDT POS invoice query response metadata.'
            && $context['response_total'] === 0
            && $context['response_datas_count'] === 0
            && $context['response_datas_is_array'] === true
        )->once();
    }

    public function test_pos_http_200_without_datas_is_rejected_not_reported_as_zero(): void
    {
        Http::fake(['*' => Http::response(['message' => 'upstream schema mismatch'], 200)]);
        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-07-01', '2026-07-31', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Malformed POS response must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cấu trúc danh sách POS không hợp lệ', $exception->getMessage());
            $this->assertStringContainsString('Không coi là 0 hóa đơn', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_pos_html_http_200_logs_safe_shape_before_rejection(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response('<html>PRIVATE_BODY</html>', 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ])]);
        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-07-01', '2026-07-31', true, 'pos',
                ['ckey' => 'PRIVATE_KEY', 'cvalue' => 'PRIVATE_ANSWER'],
            );
            $this->fail('HTML success must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cấu trúc danh sách POS không hợp lệ', $exception->getMessage());
        }
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS invoice query response metadata.') {
                return false;
            }
            $this->assertSame('html', $context['response_format']);
            $this->assertSame('scalar', $context['response_shape']);
            $this->assertSame('null', $context['datas_type']);
            $this->assertSame('null', $context['total_type']);
            $this->assertSame('text/html', $context['content_type']);
            $this->assertSame('purchase', $context['request_action']);
            $this->assertSame(15, $context['page_size']);
            $json = json_encode($context);
            foreach (['PRIVATE_BODY', 'PRIVATE_KEY', 'PRIVATE_ANSWER'] as $secret) {
                $this->assertStringNotContainsString($secret, $json);
            }
            return true;
        })->once();
        Http::assertSentCount(1);
    }

    public function test_pos_http_200_with_valid_ten_invoice_response_returns_ten(): void
    {
        Http::fake(['*' => Http::response([
            'datas' => array_fill(0, 10, ['dummy' => 'safe-fixture']),
            'total' => 10,
            'state' => null,
        ], 200)]);
        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos',
            ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
        );
        $this->assertSame(10, $result['count']);
        Http::assertSentCount(1);
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
