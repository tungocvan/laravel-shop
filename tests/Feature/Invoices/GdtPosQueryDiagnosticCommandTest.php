<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GdtPosQueryDiagnosticCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('invoices.gdt.base_url', 'https://gdt-test.invalid/api');
        config()->set('invoices.gdt.cache_key', 'gdt-pos-diagnostic-test');
        Cache::put('gdt-pos-diagnostic-test', 'TEST_SECRET_TOKEN', 600);
        Http::preventStrayRequests();
    }

    public function test_default_dry_run_does_not_call_gdt(): void
    {
        Http::fake();

        $this->artisan('invoices:gdt-pos-diagnose', [
            '--from' => '2026-10-12',
            '--to' => '2026-10-31',
        ])->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_invalid_range_does_not_call_gdt(): void
    {
        Http::fake();

        $this->artisan('invoices:gdt-pos-diagnose', [
            '--from' => '2026-10-31',
            '--to' => '2026-10-12',
            '--execute' => true,
        ])->assertExitCode(2);

        Http::assertNothingSent();
    }

    public function test_explicit_execution_sends_one_pos_get_without_persisting_or_leaking_body(): void
    {
        Http::fake(['*' => Http::response('SECRET_INVOICE_BODY', 500, ['Content-Type' => 'application/json'])]);

        $this->artisan('invoices:gdt-pos-diagnose', [
            '--from' => '2026-10-12',
            '--to' => '2026-10-31',
            '--execute' => true,
        ])->expectsOutput('HTTP status: 500')
            ->expectsOutput('Response format: json')
            ->assertExitCode(1);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_contains($request->url(), '/sco-query/invoices/purchase')
                && ($query['sort'] ?? null) === 'tdlap:desc'
                && (string) ($query['size'] ?? '') === '1'
                && str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                && ! isset($query['state'])
                && $request->hasHeader('Authorization')
                && $request->hasHeader('request-id');
        });
    }
    public function test_comparison_mode_omits_pos_filter_and_keeps_single_request(): void
    {
        Http::fake(['*' => Http::response(['datas' => [], 'total' => 0], 200)]);

        $this->artisan('invoices:gdt-pos-diagnose', [
            '--from' => '2026-10-12',
            '--to' => '2026-10-31',
            '--without-pos-filter' => true,
            '--execute' => true,
        ])->expectsOutput('HTTP status: 200')->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/sco-query/invoices/purchase')
                && ! str_contains((string) ($query['search'] ?? ''), 'ttxly==8')
                && (string) ($query['size'] ?? '') === '1';
        });
    }

}
