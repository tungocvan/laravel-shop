<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtApiService;
use Tests\TestCase;

class GdtPosAuthSessionContinuityTest extends TestCase
{
    public function test_pos_captcha_reuses_authenticated_cookie_snapshot_without_logging_values(): void
    {
        config()->set('invoices.gdt.cache_key', 'gdt-pos-continuity-test');
        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');

        Cache::put('gdt-pos-continuity-test:auth-session-cookies', [[
            'Name' => 'auth-session',
            'Value' => 'SECRET_AUTH_COOKIE',
            'Domain' => 'gdt-test.invalid',
            'Path' => '/',
            'Secure' => true,
        ]], 600);

        Log::spy();
        Http::fake(['*' => Http::response([
            'key' => 'abcdefgh1234',
            'content' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
        ], 200)]);

        app(GdtApiService::class)->loadPosCaptcha(9876);

        $challenge = Cache::get('invoices:gdt:pos-captcha:9876');
        $this->assertSame('abcdefgh1234', $challenge['key']);
        $this->assertSame('SECRET_AUTH_COOKIE', $challenge['cookies'][0]['Value']);

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            if ($message !== 'GDT POS captcha cookie lifecycle.' || ($context['stage'] ?? '') !== 'loaded') {
                return false;
            }

            $this->assertSame(1, $context['auth_cookie_count_before_captcha']);
            $this->assertStringNotContainsString('SECRET_AUTH_COOKIE', json_encode($context));

            return true;
        })->once();
    }
}
