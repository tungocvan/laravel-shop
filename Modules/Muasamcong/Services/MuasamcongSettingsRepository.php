<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class MuasamcongSettingsRepository
{
    private const MAP = [
        'origin' => 'origin',
        'verify_ssl' => 'verify_ssl',
        'timeout' => 'timeout',
        'user_agent' => 'user_agent',
        'pricing_endpoint' => 'endpoints.pricing',
        'contractor_endpoint' => 'endpoints.contractor_search',
        'portal_referer' => 'referers.portal',
        'pricing_referer' => 'referers.pricing',
        'page_size' => 'page_size',
        'smart_token' => 'smart_token',
        'session_cookie' => 'session_cookie',
    ];

    private const SECRETS = ['smart_token', 'session_cookie'];

    public function available(): bool
    {
        try {
            return Schema::hasTable('muasamcong_settings');
        } catch (Throwable) {
            return false;
        }
    }

    public function overrides(): array
    {
        if (! $this->available()) {
            return [];
        }

        $overrides = [];

        foreach (DB::table('muasamcong_settings')->get(['key', 'value', 'is_secret']) as $row) {
            $key = (string) $row->key;
            if (! isset(self::MAP[$key])) {
                continue;
            }

            if (in_array($key, self::SECRETS, true)) {
                if (! $row->is_secret) {
                    continue;
                }
                try {
                    $value = Crypt::decryptString((string) $row->value);
                } catch (Throwable) {
                    continue;
                }
            } else {
                $value = (string) $row->value;
            }

            $overrides[self::MAP[$key]] = match ($key) {
                'verify_ssl' => filter_var($value, FILTER_VALIDATE_BOOL),
                'timeout', 'page_size' => (int) $value,
                default => $value,
            };
        }

        return $overrides;
    }

    public function save(array $values): void
    {
        if (! $this->available()) {
            throw new RuntimeException('Chưa có bảng muasamcong_settings. Cần chạy migration module trước khi lưu.');
        }

        foreach ($values as $key => $value) {
            if (! isset(self::MAP[$key])) {
                throw new RuntimeException('Khóa cấu hình không hợp lệ.');
            }
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $secret = in_array($key, self::SECRETS, true);
                DB::table('muasamcong_settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => $secret ? Crypt::encryptString((string) $value) : (string) $value,
                        'is_secret' => $secret,
                        'updated_at' => now(),
                    ]
                );
            }
        });
    }

    public function apply(): void
    {
        foreach ($this->overrides() as $key => $value) {
            if ($key === 'verify_ssl' && app()->environment('production') && ! $value) {
                $value = true;
            }

            config()->set('muasamcong.'.$key, $value);
        }
    }
}
