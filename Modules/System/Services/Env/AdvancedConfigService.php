<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\System\Services\SettingsService;
use RuntimeException;
use Throwable;

class AdvancedConfigService
{
    private const QUEUE_DRIVERS = ['sync', 'database', 'redis'];

    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function defaultNodeUrl(): string
    {
        return app()->environment('production') ? 'http://socket:6001' : 'http://127.0.0.1:6001';
    }

    private function read(string $key, string $default = ''): string
    {
        try {
            $value = $this->settings->get($key, $default);
            return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
        } catch (Throwable $e) {
            Log::warning('Advanced setting unavailable.', ['key' => $key, 'exception' => $e::class]);
            return $default;
        }
    }

    private function bridgeSecret(): string
    {
        $stored = $this->read('bridge.secret');
        if ($stored === '') {
            return '';
        }
        if (!str_starts_with($stored, 'encrypted:')) {
            Log::warning('Bridge secret storage format invalid.');
            return '';
        }
        try {
            return Crypt::decryptString(substr($stored, strlen('encrypted:')));
        } catch (Throwable $e) {
            Log::warning('Bridge secret unavailable.', ['exception' => $e::class]);
            return '';
        }
    }

    public function publicValues(): array
    {
        return [
            'QUEUE_CONNECTION' => (string) config('queue.default', 'database'),
            'NODEJS_SERVER_URL' => $this->read('nodejs.server_url', $this->defaultNodeUrl()),
            'BRIDGE_SECRET_KEY' => '',
        ];
    }

    public function resolveForOperation(array $form): array
    {
        return [
            'url' => (string) ($form['NODEJS_SERVER_URL'] ?? $this->defaultNodeUrl()),
            'secret' => ($form['BRIDGE_SECRET_KEY'] ?? '') !== ''
                ? (string) $form['BRIDGE_SECRET_KEY']
                : $this->bridgeSecret(),
        ];
    }

    /**
     * Server-side credential for bridge clients. Never expose in Livewire public properties.
     * Empty means unconfigured; callers must fail closed rather than use an env fallback.
     */
    public function serverCredentials(): array
    {
        return [
            'url' => $this->serverUrl(),
            'secret' => $this->bridgeSecret(),
        ];
    }

    public function serverUrl(): string
    {
        return $this->read('nodejs.server_url', $this->defaultNodeUrl());
    }

    public function save(array $form): bool
    {
        $driver = (string) ($form['QUEUE_CONNECTION'] ?? '');
        if (!in_array($driver, self::QUEUE_DRIVERS, true)) {
            throw new InvalidArgumentException('Unsupported queue connection.');
        }

        // Queue worker driver is boot-time infrastructure; do not pretend that a DB write reconfigures it.
        if ($driver !== (string) config('queue.default', 'database')) {
            throw new InvalidArgumentException('Queue driver is managed by runtime infrastructure and cannot be changed here.');
        }

        $url = trim((string) ($form['NODEJS_SERVER_URL'] ?? ''));
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('Invalid NodeJS URL.');
        }

        $lock = Cache::lock('system:advanced-config:update', 10);
        if (!$lock->get()) {
            throw new RuntimeException('System configuration update is already in progress.');
        }

        try {
            $values = ['nodejs.server_url' => $url];
            $secret = (string) ($form['BRIDGE_SECRET_KEY'] ?? '');
            if ($secret !== '') {
                $values['bridge.secret'] = 'encrypted:'.Crypt::encryptString($secret);
            }
            $this->settings->updateMany($values, 'runtime');
            return true;
        } finally {
            $lock->release();
        }
    }
}
