<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class GdtConfigService
{
    private const ENV_KEYS = [
        'GDT_API_BASE_URL',
        'GDT_API_USERNAME',
        'GDT_API_PASSWORD',
        'GDT_API_VERIFY_SSL',
        'GDT_API_TIMEOUT',
        'GDT_TOKEN_TTL',
        'GDT_TOKEN_CACHE_KEY',
    ];

    private const DEFAULTS = [
        'GDT_API_BASE_URL' => 'https://hoadondientu.gdt.gov.vn/api',
        'GDT_API_USERNAME' => '',
        'GDT_API_PASSWORD' => '',
        'GDT_API_VERIFY_SSL' => 'true',
        'GDT_API_TIMEOUT' => '15',
        'GDT_TOKEN_TTL' => '36000',
        'GDT_TOKEN_CACHE_KEY' => 'gdt_token',
    ];

    public function ensureDefaults(): bool
    {
        $envPath = base_path('.env');

        if (! File::isFile($envPath) || ! File::isWritable($envPath)) {
            throw new RuntimeException('File .env không tồn tại hoặc không có quyền ghi.');
        }

        $content = (string) File::get($envPath);
        $missing = array_filter(
            self::DEFAULTS,
            fn (string $value, string $key): bool => preg_match(
                '/^'.preg_quote($key, '/').'\\s*=/m',
                $content
            ) !== 1,
            ARRAY_FILTER_USE_BOTH
        );

        if ($missing === []) {
            return false;
        }

        $this->update($missing);

        return true;
    }

    public function update(array $values): void
    {
        $envPath = base_path('.env');

        if (! File::isFile($envPath) || ! File::isWritable($envPath)) {
            throw new RuntimeException('File .env không tồn tại hoặc không có quyền ghi.');
        }

        $content = (string) File::get($envPath);

        foreach (self::ENV_KEYS as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = (string) $values[$key];

            if (str_contains($value, "\n") || str_contains($value, "\r")) {
                throw new RuntimeException("Giá trị {$key} không hợp lệ.");
            }

            $line = $key.'='.$this->quote($value);
            $pattern = '/^'.preg_quote($key, '/').'\\s*=.*$/m';

            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }
        }

        if (File::put($envPath, $content, true) === false) {
            throw new RuntimeException('Không thể cập nhật file .env.');
        }
    }

    private function quote(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\\s|#|"|=/', $value) === 1) {
            return '"'.addcslashes($value, '\\"').'"';
        }

        return $value;
    }
}
