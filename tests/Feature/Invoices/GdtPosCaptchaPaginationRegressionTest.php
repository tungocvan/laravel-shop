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
}
