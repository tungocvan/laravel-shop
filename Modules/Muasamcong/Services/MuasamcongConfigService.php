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

    private const DEFAULTS = [
        'MUASAMCONG_ORIGIN' => 'https://muasamcong.mpi.gov.vn',
        'MUASAMCONG_VERIFY_SSL' => 'true',
        'MUASAMCONG_TIMEOUT' => '20',
        'MUASAMCONG_USER_AGENT' => 'Mozilla/5.0 (compatible; Laravel Muasamcong Module)',
        'MUASAMCONG_SMART_TOKEN' => '',
        'MUASAMCONG_SESSION_COOKIE' => '',
        'MUASAMCONG_PRICING_ENDPOINT' => 'https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/smart/search_prc',
        'MUASAMCONG_CONTRACTOR_ENDPOINT' => 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search',
        'MUASAMCONG_PORTAL_REFERER' => 'https://muasamcong.mpi.gov.vn/',
        'MUASAMCONG_PRICING_REFERER' => 'https://muasamcong.mpi.gov.vn/web/guest/profile-info?menu=bid-pricing',
        'MUASAMCONG_PAGE_SIZE' => '20',
    ];

    public function ensureDefaults(): bool
    {
        $content = $this->content();
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
        $content = $this->content();

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

        if (File::put(base_path('.env'), $content, true) === false) {
            throw new RuntimeException('Không thể cập nhật file .env.');
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
