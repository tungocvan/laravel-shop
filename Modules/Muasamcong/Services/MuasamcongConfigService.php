<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class MuasamcongConfigService
{
    private const ENV_KEYS = [
        'MUASAMCONG_ORIGIN',
        'MUASAMCONG_VERIFY_SSL',
        'MUASAMCONG_TIMEOUT',
        'MUASAMCONG_USER_AGENT',
        'MUASAMCONG_SMART_TOKEN',
        'MUASAMCONG_SESSION_COOKIE',
        'MUASAMCONG_PRICING_ENDPOINT',
        'MUASAMCONG_CONTRACTOR_ENDPOINT',
        'MUASAMCONG_PORTAL_REFERER',
        'MUASAMCONG_PRICING_REFERER',
        'MUASAMCONG_PAGE_SIZE',
    ];

    private const NETWORK_KEYS = [
        'MUASAMCONG_ORIGIN',
        'MUASAMCONG_PRICING_ENDPOINT',
        'MUASAMCONG_CONTRACTOR_ENDPOINT',
        'MUASAMCONG_PORTAL_REFERER',
        'MUASAMCONG_PRICING_REFERER',
    ];

    public function update(array $values): void
    {
        $this->validateValues($values);

        $content = $this->content();

        foreach (self::ENV_KEYS as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = (string) $values[$key];
            $line = $key.'='.$this->quote($value);
            $pattern = '/^'.preg_quote($key, '/').'\\s*=.*$/m';

            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content, 1);
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }
        }

        if (File::put(base_path('.env'), $content, true) === false) {
            throw new RuntimeException('Không thể cập nhật file .env.');
        }
    }

    private function validateValues(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! in_array($key, self::ENV_KEYS, true)) {
                throw new RuntimeException("Biến cấu hình {$key} không được phép cập nhật.");
            }

            $value = (string) $value;

            if (str_contains($value, "\n") || str_contains($value, "\r")) {
                throw new RuntimeException("Giá trị {$key} không hợp lệ.");
            }

            if (in_array($key, self::NETWORK_KEYS, true)) {
                $this->assertApprovedUrl($key, $value);
            }
        }

        if (app()->environment('production')
            && array_key_exists('MUASAMCONG_VERIFY_SSL', $values)
            && filter_var($values['MUASAMCONG_VERIFY_SSL'], FILTER_VALIDATE_BOOL) !== true) {
            throw new RuntimeException('Không được tắt xác minh SSL trong môi trường production.');
        }
    }

    private function assertApprovedUrl(string $key, string $url): void
    {
        $parts = parse_url($url);
        $allowedHost = (string) config('muasamcong.allowed_host', 'muasamcong.mpi.gov.vn');

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || strcasecmp((string) ($parts['host'] ?? ''), $allowedHost) !== 0
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
            throw new RuntimeException("Giá trị {$key} phải dùng HTTPS và host {$allowedHost}.");
        }
    }

    private function content(): string
    {
        $path = base_path('.env');

        if (! File::isFile($path) || ! File::isWritable($path)) {
            throw new RuntimeException('File .env không tồn tại hoặc không có quyền ghi.');
        }

        return (string) File::get($path);
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
