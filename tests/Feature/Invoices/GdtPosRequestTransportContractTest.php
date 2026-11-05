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

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/sco-query/invoices/purchase')) {
                return false;
            }

            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['sort'] ?? null) === 'tdlap:desc'
                && (string) ($query['size'] ?? '') === '15'
                && ($query['search'] ?? null) === 'tdlap=ge=01/07/2026T00:00:00;tdlap=le=31/07/2026T23:59:59;ttxly==8'
                && ($query['ckey'] ?? null) === 'SECRET_CKEY'
                && ($query['cvalue'] ?? null) === 'SECRET_CVALUE'
                && $request->hasHeader('Authorization')
                && $request->header('User-Agent')[0] === 'GDT-Transport-Contract-Test/1.0'
                && $request->header('Accept-Language')[0] === 'vi'
                && $request->header('End-Point')[0] === '/tra-cuu/tra-cuu-hoa-don'
                && $request->header('ckey')[0] === 'SECRET_CKEY'
                && $request->header('cvalue')[0] === 'SECRET_CVALUE'
                && $request->header('Action')[0] === rawurlencode('Tìm kiếm (hóa đơn máy tính tiền mua vào)')
                && $request->header('Referer')[0] === 'https://gdt-test.invalid/tra-cuu/tra-cuu-hoa-don'
                && $request->hasHeader('request-id');
        });

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
