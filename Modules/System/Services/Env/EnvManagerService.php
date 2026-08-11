<?php

namespace Modules\System\Services\Env;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class EnvManagerService
{
    protected string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    public function exportToEnvironment(string $suffix): bool
    {
        $targetPath = base_path(".env.{$suffix}");

        return File::exists($this->envPath)
            ? File::copy($this->envPath, $targetPath)
            : false;
    }

    public function getValues(): array
    {
        if (!File::exists($this->envPath)) {
            return [];
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $settings = [];

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $settings[trim($key)] = $this->unquoteValue(trim($value));
        }

        return $settings;
    }

    /**
     * Transactional .env update for Docker single-file bind mounts.
     *
     * - serializes values using dotenv-safe quoting
     * - validates the complete candidate before touching .env
     * - creates a persistent safety backup
     * - writes in-place so the inode never changes
     * - restores the original content in-place if the write fails
     */
    public function update(array $data): bool
    {
        if (!File::exists($this->envPath)) {
            return false;
        }

        $original = File::get($this->envPath);
        $candidate = $this->buildCandidate($original, $data);

        $this->validateContent($candidate);
        $this->createSafetyBackup($original);

        try {
            return $this->writeInPlace($candidate);
        } catch (Throwable $e) {
            try {
                $this->writeInPlace($original);
            } catch (Throwable) {
                // Keep the original exception; the persistent backup remains available.
            }

            throw $e;
        }
    }

    protected function buildCandidate(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $key = strtoupper((string) $key);

            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
                throw new RuntimeException("Tên biến .env không hợp lệ: {$key}");
            }

            $newLine = $key . '=' . $this->formatValue($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $newLine, $content, 1);
            } else {
                $content = rtrim($content, "\r\n") . "\n{$newLine}\n";
            }
        }

        return $content;
    }

    protected function validateContent(string $content): void
    {
        try {
            Dotenv::parse($content);
        } catch (Throwable $e) {
            throw new RuntimeException('Nội dung .env sau cập nhật không hợp lệ: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function createSafetyBackup(string $content): void
    {
        $dir = storage_path('app/backups/env');

        if (!File::isDirectory($dir) && !File::makeDirectory($dir, 0700, true)) {
            throw new RuntimeException("Không thể tạo thư mục backup .env: {$dir}");
        }

        @chmod($dir, 0700);

        $path = $dir . '/.env.backup_' . now()->format('Ymd_His_u');
        $written = @file_put_contents($path, $content, LOCK_EX);

        if ($written === false || $written !== strlen($content)) {
            throw new RuntimeException("Không thể tạo backup .env: {$path}");
        }

        @chmod($path, 0600);
    }

    protected function writeInPlace(string $content): bool
    {
        $handle = @fopen($this->envPath, 'r+');

        if ($handle === false) {
            throw new RuntimeException("Không thể mở file .env để cập nhật: {$this->envPath}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Không thể lock file .env để cập nhật.');
            }

            if (!rewind($handle) || !ftruncate($handle, 0) || !rewind($handle)) {
                throw new RuntimeException('Không thể chuẩn bị file .env để ghi in-place.');
            }

            $length = strlen($content);
            $written = 0;

            while ($written < $length) {
                $result = fwrite($handle, substr($content, $written));

                if ($result === false || $result === 0) {
                    throw new RuntimeException('Không thể ghi đầy đủ nội dung file .env.');
                }

                $written += $result;
            }

            if (!fflush($handle)) {
                throw new RuntimeException('Không thể flush file .env.');
            }

            if (function_exists('fsync')) {
                @fsync($handle);
            }

            return true;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function unquoteValue(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return stripcslashes(substr($value, 1, -1));
            }
        }

        return $value;
    }

    protected function formatValue(mixed $value): string
    {
        if ($value === null) {
            $value = '';
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (!is_scalar($value)) {
            throw new RuntimeException('Giá trị .env phải là scalar hoặc null.');
        }

        $value = (string) $value;

        // Keep only conservative values unquoted. Everything else is encoded
        // as a double-quoted dotenv value so spaces, #, $, quotes and URLs are safe.
        if ($value !== '' && preg_match('/^[A-Za-z0-9_\.\/:@+\-]+$/', $value)) {
            return $value;
        }

        $escaped = str_replace(
            ["\\", '"', '$', "\r", "\n"],
            ["\\\\", '\\"', '\\$', '\\r', '\\n'],
            $value
        );

        return '"' . $escaped . '"';
    }
}
