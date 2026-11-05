<?php

namespace Modules\Invoices\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiagnoseGdtPosQueryCommand extends Command
{
    protected $signature = 'invoices:gdt-pos-diagnose
        {--from= : Start date YYYY-MM-DD}
        {--to= : End date YYYY-MM-DD}
        {--type=purchase : purchase or sold}
        {--without-pos-filter : Diagnostic comparison only: omit ttxly==8}
        {--execute : Explicitly authorize one read-only GDT HTTP request}';

    protected $description = 'Inspect one POS list request; no invoice persistence, export, or raw response body output';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $type = $this->option('type');

        if (! is_string($from) || ! is_string($to)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $from)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $to)
            || ! in_array($type, ['purchase', 'sold'], true)) {
            $this->error('Specify --from=YYYY-MM-DD --to=YYYY-MM-DD and --type=purchase|sold.');

            return self::INVALID;
        }

        try {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $from);
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $to);
        } catch (\Throwable) {
            $this->error('Invalid date.');

            return self::INVALID;
        }

        if (! $start || ! $end || $start->format('Y-m-d') !== $from
            || $end->format('Y-m-d') !== $to || $start->gt($end)
            || $start->diffInDays($end) > 31) {
            $this->error('Date range must be valid, ordered, and no longer than 31 days.');

            return self::INVALID;
        }

        $this->line('Source: POS; action: '.$type.'; first page only; size: 1; no persistence.');
        $withoutFilter = (bool) $this->option('without-pos-filter');
        $this->line('Range: '.$from.' to '.$to.'; search '.($withoutFilter ? 'omits' : 'includes').' ttxly==8.');
        if (! $this->option('execute')) {
            $this->warn('DRY RUN: no HTTP request sent. Add --execute to authorize one GET.');

            return self::SUCCESS;
        }

        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! is_string($token) || $token === '') {
            $this->error('No cached GDT session. Log in through the existing UI first.');

            return self::FAILURE;
        }

        $base = rtrim((string) config('invoices.gdt.base_url'), '/');
        if (! str_starts_with($base, 'https://')) {
            $this->error('GDT base URL must use HTTPS.');

            return self::FAILURE;
        }

        $origin = preg_replace('#/api$#', '', $base) ?: $base;
        $search = 'tdlap=ge='.$start->format('d/m/Y').'T00:00:00;tdlap=le='
            .$end->format('d/m/Y').'T23:59:59'.($withoutFilter ? '' : ';ttxly==8');

        try {
            $response = Http::withOptions(['verify' => (bool) config('invoices.gdt.verify_ssl', true)])
                ->connectTimeout(10)
                ->timeout(min(30, max(5, (int) config('invoices.gdt.timeout', 15))))
                ->withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json, text/plain, */*',
                    'Origin' => $origin,
                    'Referer' => $origin.'/',
                    'Action' => '',
                    'End-Point' => '/',
                    'request-id' => (string) Str::uuid(),
                ])
                ->get($base.'/sco-query/invoices/'.$type, [
                    'sort' => 'tdlap:desc',
                    'size' => 1,
                    'search' => $search,
                ]);
        } catch (ConnectionException) {
            $this->error('GDT connection failed (details suppressed).');

            return self::FAILURE;
        }

        $contentType = strtolower((string) $response->header('Content-Type', ''));
        $format = str_contains($contentType, 'json') ? 'json'
            : (str_contains($contentType, 'html') ? 'html' : 'other');

        $this->line('HTTP status: '.$response->status());
        $this->line('Response format: '.$format);
        // Inspect only top-level, allowlisted error fields. Never print arbitrary remote strings:
        // even a "message" may contain invoice identifiers or credentials.
        if ($format === 'json') {
            $payload = $response->json();
            if (is_array($payload)) {
                foreach (['code', 'status', 'error', 'message'] as $field) {
                    if (! array_key_exists($field, $payload)) {
                        continue;
                    }

                    $value = $payload[$field];
                    $kind = match (true) {
                        is_string($value) => 'string',
                        is_int($value), is_float($value) => 'number',
                        is_bool($value) => 'boolean',
                        is_null($value) => 'null',
                        default => 'structured',
                    };
                    $this->line('Error field '.$field.': present ('.$kind.').');

                    // Only strictly numeric codes or uppercase symbolic codes can be printed.
                    // Never display a free-form error/message or nested data.
                    if ($field === 'code' && (is_string($value) || is_int($value))) {
                        $code = (string) $value;
                        if (preg_match('/^(?:[0-9]{1,6}|[A-Z][A-Z0-9_]{0,31})$/D', $code)) {
                            $this->line('Safe error code: '.$code);
                        }
                    }
                }
            } else {
                $this->line('JSON response is not an object.');
            }
        }

        $this->line('No raw response body, identifiers, tokens, or query values were output.');

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
