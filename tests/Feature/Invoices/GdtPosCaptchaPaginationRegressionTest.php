<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtPosCaptchaPaginationRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('invoices.gdt.cache_key', 'gdt-pos-pagination-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-pagination-test-token', 'fake-token', 600);
    }

    public function test_pos_page_two_captcha_401_keeps_login_token_and_rejects_partial_results(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response([
                    'datas' => array_fill(0, 15, ['nbmst' => 'test-fixture']),
                    'total' => 17,
                    'state' => 'next-page',
                ], 200)
                : Http::response([
                    'message' => 'Mã captcha không đúng.',
                    'details' => [],
                ], 401);
        });

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-09-01', '2026-09-30', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Partial POS preview must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('CAPTCHA POS tại trang 2', $exception->getMessage());
            $this->assertStringContainsString('không tạo file thiếu', $exception->getMessage());
            $this->assertStringNotContainsString('hết hạn', $exception->getMessage());
        }

        $this->assertSame(2, $calls);
        $this->assertSame('fake-token', Cache::get('gdt-pos-pagination-test-token'));
        Http::assertSentCount(2);
        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        parse_str((string) parse_url($requests[1]->url(), PHP_URL_QUERY), $query);
        $this->assertSame('next-page', $query['state'] ?? null);
        $this->assertArrayNotHasKey('ckey', $query);
        $this->assertArrayNotHasKey('cvalue', $query);
        $this->assertFalse($requests[1]->hasHeader('ckey'));
        $this->assertFalse($requests[1]->hasHeader('cvalue'));
        $this->assertTrue($requests[0]->hasHeader('ckey'));
        $this->assertTrue($requests[0]->hasHeader('cvalue'));
    }


    public function test_pos_page_two_uses_state_without_replaying_captcha_and_returns_complete_results(): void
    {
        $calls = 0;
        Http::fake(function ($request) use (&$calls) {
            $calls++;
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if ($calls === 1) {
                $this->assertSame('fake-key', $query['ckey'] ?? null);
                $this->assertSame('fake-answer', $query['cvalue'] ?? null);
                $this->assertArrayNotHasKey('state', $query);

                return Http::response([
                    'datas' => array_fill(0, 15, ['nbmst' => 'fixture']),
                    'total' => 17,
                    'state' => 'opaque-next-page',
                ], 200);
            }

            $this->assertSame('opaque-next-page', $query['state'] ?? null);
            $this->assertArrayNotHasKey('ckey', $query);
            $this->assertArrayNotHasKey('cvalue', $query);
            $this->assertFalse($request->hasHeader('ckey'));
            $this->assertFalse($request->hasHeader('cvalue'));

            return Http::response([
                'datas' => array_fill(0, 2, ['nbmst' => 'fixture']),
                'total' => 17,
                'state' => null,
            ], 200);
        });

        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-09-01', '2026-09-30', true, 'pos',
            ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
        );

        $this->assertSame(17, $result['count']);
        $this->assertSame(2, $calls);
        $this->assertSame('fake-token', Cache::get('gdt-pos-pagination-test-token'));
        Http::assertSentCount(2);
    }

    public function test_real_authentication_401_still_invalidates_token(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-09-01', '2026-09-30', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('Expected authentication rejection.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Phiên đăng nhập GDT đã hết hạn', $exception->getMessage());
        }

        $this->assertNull(Cache::get('gdt-pos-pagination-test-token'));
        Http::assertSentCount(1);
    }

    public function test_pos_pagination_keeps_mutable_cookie_jar_across_pages(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());

        $this->assertStringContainsString('$posJar = new CookieJar;', $source);
        $this->assertStringContainsString('$jar = $posJar;', $source);
        $this->assertSame(1, substr_count($source, '$posJar = new CookieJar;'));
        $this->assertStringContainsString("if (\$res->status() === 401 && ! \$isPosCaptchaRejection)", $source);
    }
    public function test_pos_page_two_http_500_fails_closed_and_preserves_login_token(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response([
                    'datas' => array_fill(0, 15, ['nbmst' => 'fixture']),
                    'total' => 17,
                    'state' => 'opaque-next-page',
                ], 200)
                : Http::response(['message' => 'Upstream internal error'], 500);
        });

        try {
            app(GdtInvoiceService::class)->previewSource(
                '2026-09-01', '2026-09-30', true, 'pos',
                ['ckey' => 'fake-key', 'cvalue' => 'fake-answer'],
            );
            $this->fail('A 15/17 partial preview must not succeed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('GDT POS lỗi phân trang tại trang 2 (HTTP 500)', $exception->getMessage());
            $this->assertStringContainsString('đã nhận 15/17 hóa đơn', $exception->getMessage());
            $this->assertStringContainsString('CAPTCHA mới', $exception->getMessage());
            $this->assertStringContainsString('không kết luận CAPTCHA hết hạn', $exception->getMessage());
            $this->assertStringContainsString('Không tạo file thiếu', $exception->getMessage());
        }

        $this->assertSame(2, $calls);
        $this->assertSame('fake-token', Cache::get('gdt-pos-pagination-test-token'));
        Http::assertSentCount(2);
    }

    public function test_pos_http_500_diagnostics_never_log_upstream_json_message_or_payload(): void
    {
        $source = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());

        $this->assertStringContainsString("'pos_error_shape' => \\$posErrorShape", $source);
        $this->assertStringContainsString("'pos_error_message_category' => \\$posErrorMessageCategory", $source);
        $this->assertStringContainsString("'pos_error_has_message_field' =>", $source);
        $this->assertStringNotContainsString("'response_body' =>", $source);
    }

}
