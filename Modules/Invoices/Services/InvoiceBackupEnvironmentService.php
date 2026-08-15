<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class InvoiceBackupEnvironmentService
{
    private const DEFAULTS = [
        'INVOICES_BACKUP_AUTOMATIC_ENABLED' => 'false',
        'INVOICES_BACKUP_SCHEDULE_DAY' => '1',
        'INVOICES_BACKUP_SCHEDULE_TIME' => '00:15',
        'INVOICES_BACKUP_EMAIL_CHUNK_BYTES' => '12582912',
    ];

    public function status(): array
    {
        $envPath = base_path('.env');
        $docker = $this->isDocker();
        $production = app()->environment('production');
        $protected = $docker || $production;
        $values = $this->currentValues($envPath);
        $required = array_keys(self::DEFAULTS);
        array_unshift($required, 'INVOICES_BACKUP_EMAIL');
        $missing = array_values(array_filter($required, fn (string $key) => ! array_key_exists($key, $values)));

        return [
            'docker' => $docker,
            'production' => $production,
            'protected' => $protected,
            'env_exists' => is_file($envPath),
            'env_writable' => is_file($envPath) && is_writable($envPath),
            'missing' => $missing,
            'configured' => $missing === [],
            'defaults' => $this->defaults(),
            'snippet' => $this->snippet($missing === [] ? $required : $missing),
        ];
    }

    public function installMissing(): array
    {
        $status = $this->status();
        if ($status['protected']) {
            throw new RuntimeException('Môi trường Docker/VPS/production không cho phép sửa .env từ UI. Hãy cấu hình biến môi trường tại .env/Compose/secret rồi restart container hoặc clear config cache.');
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $example = base_path('.env.example');
            if (! is_file($example) || ! @copy($example, $envPath)) {
                throw new RuntimeException('Không tìm thấy .env và không thể tạo từ .env.example.');
            }
        }
        if (! is_writable($envPath)) {
            throw new RuntimeException('File .env không có quyền ghi.');
        }

        $values = $this->currentValues($envPath);
        $defaults = $this->defaults();
        $added = [];
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $values)) {
                continue;
            }
            $added[$key] = $value;
        }

        if ($added === []) {
            return ['added' => [], 'status' => $this->status()];
        }

        $block = PHP_EOL.'# Invoices automatic backup'.PHP_EOL;
        foreach ($added as $key => $value) {
            $block .= $key.'='.$this->quoteIfNeeded($value).PHP_EOL;
        }
        if (@file_put_contents($envPath, $block, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Không thể ghi cấu hình automatic backup vào .env.');
        }

        try {
            Artisan::call('config:clear');
        } catch (\Throwable) {
            // The values are already persisted; a manual optimize:clear can be run later.
        }

        return ['added' => array_keys($added), 'status' => $this->status()];
    }

    private function defaults(): array
    {
        $email = (string) config('mail.from.address', '');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($email), '@example.com')) {
            $email = '';
        }

        return ['INVOICES_BACKUP_EMAIL' => $email] + self::DEFAULTS;
    }

    private function snippet(array $keys): string
    {
        $defaults = $this->defaults();
        $lines = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $defaults)) continue;
            $lines[] = $key.'='.$this->quoteIfNeeded($defaults[$key]);
        }
        return implode("\n", $lines);
    }

    private function currentValues(string $envPath): array
    {
        if (! is_file($envPath) || ! is_readable($envPath)) return [];
        $values = [];
        foreach (file($envPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) continue;
            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            if ($key !== '') $values[$key] = trim($value);
        }
        return $values;
    }

    private function quoteIfNeeded(string $value): string
    {
        if ($value === '') return '""';
        return preg_match('/\s|#|=/', $value) ? '"'.str_replace('"', '\\"', $value).'"' : $value;
    }

    private function isDocker(): bool
    {
        if (is_file('/.dockerenv')) return true;
        $cgroup = @file_get_contents('/proc/1/cgroup') ?: '';
        return str_contains($cgroup, 'docker') || str_contains($cgroup, 'containerd') || str_contains($cgroup, 'kubepods');
    }
}
