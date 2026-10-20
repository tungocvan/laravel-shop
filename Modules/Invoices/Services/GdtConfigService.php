<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GdtConfigService
{
    public const KEYS = [
        'GDT_API_BASE_URL' => 'base_url',
        'GDT_API_USERNAME' => 'username',
        'GDT_API_PASSWORD' => 'password',
        'GDT_API_VERIFY_SSL' => 'verify_ssl',
        'GDT_API_TIMEOUT' => 'timeout',
        'GDT_TOKEN_TTL' => 'token_ttl',
        'GDT_TOKEN_CACHE_KEY' => 'cache_key',
    ];

    public function ensureDefaults(): bool
    {
        return false;
    }

    public function hydrate(): void
    {
        if (! Schema::hasTable('invoice_gdt_settings')) {
            return;
        }

        foreach (DB::table('invoice_gdt_settings')->get(['key', 'value']) as $row) {
            if (! isset(self::KEYS[$row->key])) {
                continue;
            }

            $value = $row->key === 'GDT_API_PASSWORD'
                ? Crypt::decryptString($row->value)
                : $row->value;

            if ($row->key === 'GDT_API_VERIFY_SSL') {
                $value = $value === 'true';
            } elseif (in_array($row->key, ['GDT_API_TIMEOUT', 'GDT_TOKEN_TTL'], true)) {
                $value = (int) $value;
            }

            config()->set('invoices.gdt.'.self::KEYS[$row->key], $value);
        }
    }

    public function update(array $values): void
    {
        if (! Schema::hasTable('invoice_gdt_settings')) {
            throw new RuntimeException('Chưa có bảng invoice_gdt_settings.');
        }

        if (array_diff(array_keys($values), array_keys(self::KEYS))) {
            throw new RuntimeException('Khóa cấu hình GDT không hợp lệ.');
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $value = (string) $value;
                if (str_contains($value, "\n") || str_contains($value, "\r")) {
                    throw new RuntimeException('Giá trị cấu hình GDT không hợp lệ.');
                }

                DB::table('invoice_gdt_settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => $key === 'GDT_API_PASSWORD' ? Crypt::encryptString($value) : $value,
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $this->hydrate();
    }
}
