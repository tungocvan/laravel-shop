<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvExampleSyncService
{
    private const SECRET_PATTERNS = [
        '/(^|_)(PASSWORD|PASS|SECRET|TOKEN|COOKIE|CREDENTIALS?)(_|$)/i',
        '/(^|_)(PRIVATE_KEY|APP_KEY|BRIDGE_SECRET_KEY)(_|$)/i',
    ];

    private const EXAMPLE_OVERRIDES = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'DB_HOST' => '127.0.0.1',
        'DB_DATABASE' => 'laravel',
        'DB_USERNAME' => 'laravel',
        'REDIS_HOST' => '127.0.0.1',
    ];

    private const DOCKER_OVERRIDES = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'DB_HOST' => 'db',
        'DB_DATABASE' => 'laravel',
        'DB_USERNAME' => 'laravel',
        'REDIS_HOST' => 'redis',
    ];

    public function sync(string $sourceContent): array
    {
        $targets = [
            '.env.example' => false,
            '.env.docker.example' => true,
        ];

        $result = ['files' => [], 'keys' => 0, 'secrets_sanitized' => 0];
        foreach ($targets as $filename => $docker) {
            $path = base_path($filename);
            $template = File::isFile($path) ? (string) File::get($path) : '';
            $rendered = $this->render($sourceContent, $template, $docker);
            if (File::put($path, $rendered['content'], true) === false) {
                throw new RuntimeException("Không thể cập nhật {$filename}.");
            }
            $result['files'][] = $filename;
            $result['keys'] = max($result['keys'], $rendered['keys']);
            $result['secrets_sanitized'] = max($result['secrets_sanitized'], $rendered['secrets_sanitized']);
        }

        return $result;
    }

    public function render(string $sourceContent, string $templateContent, bool $docker = false): array
    {
        $source = $this->parseValues($sourceContent);
        $seen = [];
        $secretCount = 0;
        $lines = preg_split('/\R/', $templateContent) ?: [];
        $output = [];

        foreach ($lines as $line) {
            if (! preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $match)) {
                $output[] = $line;
                continue;
            }

            $key = $match[1];
            $seen[$key] = true;
            if (! array_key_exists($key, $source)) {
                $output[] = $line;
                continue;
            }

            $value = $this->safeValue($key, $source[$key], $docker);
            if ($this->isSecret($key)) $secretCount++;
            $output[] = $key.'='.$this->formatValue($value);
        }

        $missing = array_diff_key($source, $seen);
        if ($missing !== []) {
            if ($output !== [] && end($output) !== '') $output[] = '';
            $output[] = '# Synced from Production ENV snapshot (sanitized)';
            foreach ($missing as $key => $value) {
                $safe = $this->safeValue($key, $value, $docker);
                if ($this->isSecret($key)) $secretCount++;
                $output[] = $key.'='.$this->formatValue($safe);
            }
        }

        return [
            'content' => rtrim(implode("\n", $output))."\n",
            'keys' => count($source),
            'secrets_sanitized' => $secretCount,
        ];
    }

    private function parseValues(string $content): array
    {
        $values = [];
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (! preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $match)) continue;
            $values[$match[1]] = $this->unquote(trim($match[2]));
        }
        return $values;
    }

    private function safeValue(string $key, string $value, bool $docker): string
    {
        if ($this->isSecret($key)) return '';
        $overrides = $docker ? self::DOCKER_OVERRIDES : self::EXAMPLE_OVERRIDES;
        return array_key_exists($key, $overrides) ? $overrides[$key] : $value;
    }

    private function isSecret(string $key): bool
    {
        foreach (self::SECRET_PATTERNS as $pattern) {
            if (preg_match($pattern, $key)) return true;
        }
        return false;
    }

    private function unquote(string $value): string
    {
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    private function formatValue(string $value): string
    {
        if ($value === '') return '';
        if (preg_match('/[\s#"\'\\]/', $value)) return '"'.addcslashes($value, "\\\"").'"';
        return $value;
    }
}
