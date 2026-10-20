<?php

namespace Modules\Muasamcong\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Services\MuasamcongSettingsRepository;
use Throwable;

class AuditEnvCleanup extends Command
{
    protected $signature = 'muasamcong:audit-env-cleanup';

    protected $description = 'Read-only audit of legacy Muasamcong .env keys before manual cleanup; never prints values';

    private const MAP = [
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

    public function handle(MuasamcongSettingsRepository $settings): int
    {
        if (! $settings->available()) {
            $this->error('Settings table unavailable; no cleanup is safe.');
            return self::FAILURE;
        }

        if (! is_readable(base_path('.env'))) {
            $this->error('.env unavailable; no cleanup is safe.');
            return self::FAILURE;
        }

        try {
            $env = Dotenv::parse((string) file_get_contents(base_path('.env')));
            $stored = DB::table('muasamcong_settings')->pluck('key')->all();
            $overrides = $settings->overrides();
        } catch (Throwable) {
            $this->error('Unable to audit settings. No files changed.');
            return self::FAILURE;
        }

        $missing = false;
        foreach (self::MAP as $envKey => $dbKey) {
            if (! array_key_exists($envKey, $env)) {
                $this->line($envKey.': ABSENT');
                continue;
            }

            $configKey = match ($dbKey) {
                'pricing_endpoint' => 'endpoints.pricing',
                'contractor_endpoint' => 'endpoints.contractor_search',
                'portal_referer' => 'referers.portal',
                'pricing_referer' => 'referers.pricing',
                default => $dbKey,
            };

            if (! in_array($dbKey, $stored, true) || ! array_key_exists($configKey, $overrides)
                || $overrides[$configKey] !== config('muasamcong.'.$configKey)) {
                $this->line($envKey.': BLOCKED (DB missing, unreadable, or runtime mismatch)');
                $missing = true;
                continue;
            }

            $this->line($envKey.': CANDIDATE (DB override matches runtime)');
        }

        $this->warn('READ-ONLY: no .env changes. CANDIDATE does not authorize deletion; audit other consumers, backup, rollback and environment-specific overrides first.');
        return $missing ? self::FAILURE : self::SUCCESS;
    }
}
