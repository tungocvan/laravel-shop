<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Models\Setting;
use Throwable;

final class RealtimeManager
{
    public function enabled(): bool
    {
        if (! config('realtime.allowed', true)) {
            return false;
        }

        $default = (bool) config('realtime.default_enabled', true);

        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            return filter_var(
                Setting::getValue('realtime_enabled', $default ? '1' : '0'),
                FILTER_VALIDATE_BOOL
            );
        } catch (Throwable) {
            return $default;
        }
    }

    public function setEnabled(bool $enabled): void
    {
        if ($enabled && ! config('realtime.allowed', true)) {
            throw new \LogicException('Realtime bị khóa bởi REALTIME_ALLOWED.');
        }

        Setting::setValue('realtime_enabled', $enabled ? '1' : '0', 'realtime', 'boolean');
    }

    public function health(): array
    {
        $url = $this->healthUrl();

        if (! $this->enabled()) {
            return ['status' => 'disabled', 'online' => false, 'url' => $url, 'clients' => null];
        }

        try {
            $response = Http::timeout((int) config('realtime.health_timeout', 2))->get($url);
            $data = $response->json();

            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'online' => $response->successful(),
                'url' => $url,
                'clients' => is_array($data) ? ($data['clients'] ?? null) : null,
            ];
        } catch (Throwable) {
            return ['status' => 'offline', 'online' => false, 'url' => $url, 'clients' => null];
        }
    }

    public function browserConfig(): array
    {
        return [
            'enabled' => $this->enabled(),
            'url' => config('services.nodejs.public_url'),
        ];
    }

    private function healthUrl(): string
    {
        return (string) (config('realtime.health_url')
            ?: rtrim((string) config('services.nodejs.url'), '/') . '/health');
    }
}
