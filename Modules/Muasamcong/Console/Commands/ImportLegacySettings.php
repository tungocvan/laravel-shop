<?php

namespace Modules\Muasamcong\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Services\MuasamcongSettingsRepository;
use Modules\Muasamcong\Services\MuasamcongUrlValidator;
use Throwable;

class ImportLegacySettings extends Command
{
    protected $signature = 'muasamcong:import-env-settings {--dry-run : Preview without writing} {--force : Allow explicit execution in production}';

    protected $description = 'Import allowlisted legacy MUASAMCONG_* settings into encrypted module database settings without overwriting existing values';

    private const KEYS = [
        'MUASAMCONG_ORIGIN' => 'origin',
        'MUASAMCONG_VERIFY_SSL' => 'verify_ssl',
        'MUASAMCONG_TIMEOUT' => 'timeout',
        'MUASAMCONG_USER_AGENT' => 'user_agent',
        'MUASAMCONG_PRICING_ENDPOINT' => 'pricing_endpoint',
        'MUASAMCONG_CONTRACTOR_ENDPOINT' => 'contractor_endpoint',
        'MUASAMCONG_PORTAL_REFERER' => 'portal_referer',
        'MUASAMCONG_PRICING_REFERER' => 'pricing_referer',
        'MUASAMCONG_PAGE_SIZE' => 'page_size',
        'MUASAMCONG_SMART_TOKEN' => 'smart_token',
        'MUASAMCONG_SESSION_COOKIE' => 'session_cookie',
    ];

    public function handle(MuasamcongSettingsRepository $settings, MuasamcongUrlValidator $urls): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && app()->environment('production') && ! $this->option('force')) {
            $this->error('Production import requires explicit --force after an approved change window.');
            return self::FAILURE;
        }

        if (! $settings->available()) {
            $this->error('Missing muasamcong_settings table. Import aborted.');
            return self::FAILURE;
        }

        $path = base_path('.env');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('Local .env is unavailable or unreadable. Import aborted.');
            return self::FAILURE;
        }

        try {
            $env = Dotenv::parse((string) file_get_contents($path));
            $existing = DB::table('muasamcong_settings')->pluck('key')->all();
        } catch (Throwable) {
            $this->error('Unable to parse .env or read existing settings. Import aborted.');
            return self::FAILURE;
        }

        $values = [];
        $skipped = [];
        $errors = [];

        foreach (self::KEYS as $envKey => $dbKey) {
            if (! array_key_exists($envKey, $env) || trim((string) $env[$envKey]) === '') {
                $skipped[] = $envKey.' (missing/empty)';
                continue;
            }
            if (in_array($dbKey, $existing, true)) {
                $skipped[] = $envKey.' (database override exists)';
                continue;
            }

            $value = (string) $env[$envKey];
            if (! $this->valid($dbKey, $value, $urls)) {
                $errors[] = $envKey;
                continue;
            }
            $values[$dbKey] = $value;
        }

        if ($errors !== []) {
            $this->error('Invalid settings; no values imported: '.implode(', ', $errors));
            return self::FAILURE;
        }

        foreach (self::KEYS as $envKey => $dbKey) {
            if (array_key_exists($dbKey, $values)) {
                $this->line('IMPORT '. $envKey . ($dryRun ? ' [dry-run]' : ''));
            } elseif (in_array($envKey.' (database override exists)', $skipped, true)) {
                $this->line('SKIP '.$envKey.' (database override exists)');
            } else {
                $this->line('SKIP '.$envKey.' (missing/empty)');
            }
        }

        if ($dryRun) {
            $this->info('Dry-run complete; database and .env unchanged.');
            return self::SUCCESS;
        }

        try {
            // Recheck inside the same transaction to avoid overwriting an override
            // created after the initial read.
            DB::transaction(function () use ($settings, $values): void {
                $remaining = [];
                foreach ($values as $key => $value) {
                    if (! DB::table('muasamcong_settings')->where('key', $key)->lockForUpdate()->exists()) {
                        $remaining[$key] = $value;
                    }
                }
                if ($remaining !== []) {
                    $settings->save($remaining);
                }
            });
        } catch (Throwable) {
            $this->error('Import failed. No .env content was changed.');
            return self::FAILURE;
        }

        $this->info('Import completed. .env was not modified; verify effective config before any manual cleanup.');
        return self::SUCCESS;
    }

    private function valid(string $key, string $value, MuasamcongUrlValidator $urls): bool
    {
        if (in_array($key, ['origin', 'pricing_endpoint', 'contractor_endpoint', 'portal_referer', 'pricing_referer'], true)) {
            return strlen($value) <= 1000 && $urls->isApproved($value);
        }

        return match ($key) {
            'verify_ssl' => in_array(strtolower($value), ['true', 'false', '1', '0'], true)
                && (! app()->environment('production') || in_array(strtolower($value), ['true', '1'], true)),
            'timeout' => ctype_digit($value) && (int) $value >= 1 && (int) $value <= 120,
            'page_size' => ctype_digit($value) && (int) $value >= 1 && (int) $value <= 100,
            'user_agent' => strlen($value) <= 500 && ! preg_match('/[\r\n]/', $value),
            'smart_token' => strlen($value) <= 4000 && ! preg_match('/[\r\n]/', $value),
            'session_cookie' => strlen($value) <= 16000 && ! preg_match('/[\r\n]/', $value),
            default => false,
        };
    }
}
