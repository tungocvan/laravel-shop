<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtInvoiceService;
use Tests\TestCase;

class GdtPosCaptchaFlowTest extends TestCase
{
    public function test_pos_captcha_is_loaded_and_consumed_once(): void
    {
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Http::fake(['*' => Http::response([
            'key' => 'exampleKey123456',
            'content' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
        ])]);

        $service = app(GdtApiService::class);
        $image = $service->loadPosCaptcha(456);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $image['image']);
        $challenge = $service->consumePosCaptcha(456, 'ABCD');
        $this->assertSame('exampleKey123456', $challenge['ckey']);
        $this->assertSame('ABCD', $challenge['cvalue']);
        $this->expectException(\RuntimeException::class);
        $service->consumePosCaptcha(456, 'ABCD');
    }

    public function test_pos_cookie_snapshot_survives_consume_and_is_restored_into_request_jar(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-cookie-transport-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-cookie-transport-test-token', 'fake-token', 600);
        Log::spy();
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200)]);

        $cookies = [[
            'Name' => 'gdt-session-fixture',
            'Value' => 'PRIVATE_COOKIE_VALUE',
            'Domain' => 'gdt-test.invalid',
            'Path' => '/',
            'Secure' => true,
            'HttpOnly' => true,
        ]];
        Cache::put('invoices:gdt:pos-captcha:987654', [
            'key' => 'exampleKey123456',
            'cookies' => $cookies,
        ], now()->addMinutes(5));

        $challenge = app(GdtApiService::class)->consumePosCaptcha(987654, 'ABCD');
        $this->assertSame($cookies, $challenge['cookies']);
        $this->assertNull(Cache::get('invoices:gdt:pos-captcha:987654'));
        $result = app(GdtInvoiceService::class)->previewSource(
            '2026-07-01', '2026-07-31', true, 'pos', $challenge,
        );
        $this->assertSame(0, $result['count']);
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $message === 'GDT POS captcha cookie lifecycle.'
                && ($context['stage'] ?? null) === 'request'
                && ($context['action'] ?? null) === 'purchase'
                && ($context['stored_cookie_count'] ?? null) === 1
                && ($context['jar_cookie_count'] ?? null) === 1;
        })->once();
        Http::assertSentCount(1);
    }

    public function test_pos_without_captcha_does_not_call_pos_endpoint(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-captcha-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-captcha-test-token', 'fake-token', 600);
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CAPTCHA');
        try {
            app(GdtInvoiceService::class)->processRange('2026-11-01', '2026-11-02', null, true, 'pos');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_pos_request_has_manual_captcha_and_correct_filter(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-captcha-test-token');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        Cache::put('gdt-pos-captcha-test-token', 'fake-token', 600);
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0])]);

        $result = app(GdtInvoiceService::class)->processRange(
            '2026-11-01', '2026-11-02', null, true, 'pos', null,
            ['ckey' => 'exampleKey123456', 'cvalue' => 'ABCD'],
        );
        $this->assertNull($result);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            return str_contains($request->url(), '/sco-query/invoices/purchase')
                && str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                && ($query['ckey'] ?? null) === 'exampleKey123456'
                && ($query['cvalue'] ?? null) === 'ABCD';
        });
    }
}
