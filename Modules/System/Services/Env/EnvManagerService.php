<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\File;
use RuntimeException;

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

        if (File::exists($this->envPath)) {
            return File::copy($this->envPath, $targetPath);
        }

        return false;
    }

    public function getValues(): array
    {
        if (!File::exists($this->envPath)) {
            return [];
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $settings = [];

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $settings[trim($key)] = $this->unquoteValue(trim($value));
            }
        }

        return $settings;
    }

    /**
     * Cập nhật các biến môi trường mà không thay inode của .env.
     *
     * .env được bind-mount trực tiếp vào container. Vì vậy không dùng
     * File::put()/file_put_contents() hay replace/rename file. Ghi in-place
     * trên descriptor hiện có giúp tương thích Docker single-file bind mount.
     */
    public function update(array $data): bool
    {
        if (!File::exists($this->envPath)) {
            return false;
        }

        $content = File::get($this->envPath);

        foreach ($data as $key => $value) {
            $key = strtoupper((string) $key);
            $formattedValue = $this->formatValue($value);

            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $newLine = "{$key}={$formattedValue}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $newLine, $content, 1);
            } else {
                $content = rtrim($content, "\r\n") . "\n{$newLine}\n";
            }
        }

        return $this->writeInPlace($content);
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

            if (!rewind($handle)) {
                throw new RuntimeException('Không thể rewind file .env.');
            }

            if (!ftruncate($handle, 0)) {
                throw new RuntimeException('Không thể truncate file .env.');
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
        return trim($value, "\"' ");
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

        if (preg_match('/\s/', $value) || str_contains($value, '#')) {
            return '"' . addslashes($value) . '"';
        }

        return $value;
    }
}
