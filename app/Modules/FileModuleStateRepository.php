<?php

namespace App\Modules;

use JsonException;
use RuntimeException;

class FileModuleStateRepository implements ModuleStateRepository
{
    private const FILE_MODE = 0660;

    public function __construct(private readonly string $path) {}

    public function has(string $module): bool
    {
        return array_key_exists($module, $this->all());
    }

    public function get(string $module): ?bool
    {
        $states = $this->all();

        return array_key_exists($module, $states) ? $states[$module] : null;
    }

    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read module runtime state.');
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Module runtime state contains invalid JSON.', 0, $e);
        }

        if (! is_array($data) || ($data['version'] ?? null) !== 1 || ! isset($data['modules']) || ! is_array($data['modules'])) {
            throw new RuntimeException('Module runtime state has an invalid structure.');
        }

        foreach ($data['modules'] as $module => $enabled) {
            if (! is_string($module) || $module === '' || ! is_bool($enabled)) {
                throw new RuntimeException('Module runtime state contains an invalid module value.');
            }
        }

        return $data['modules'];
    }

    public function set(string $module, bool $enabled): void
    {
        $this->mutate(function (array $states) use ($module, $enabled): array {
            $states[$module] = $enabled;

            return $states;
        });
    }

    public function forget(string $module): void
    {
        $this->mutate(function (array $states) use ($module): array {
            unset($states[$module]);

            return $states;
        });
    }

    private function mutate(callable $callback): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create module runtime state directory.');
        }

        $lockPath = $this->path.'.lock';
        $lock = fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException('Unable to open module runtime state lock.');
        }

        if (! chmod($lockPath, self::FILE_MODE)) {
            fclose($lock);
            throw new RuntimeException('Unable to set module runtime state lock permissions.');
        }

        $temporaryPath = null;

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock module runtime state.');
            }

            $states = $callback($this->all());

            try {
                $payload = json_encode([
                    'version' => 1,
                    'modules' => $states,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Unable to encode module runtime state.', 0, $e);
            }

            $temporaryPath = $this->path.'.tmp.'.bin2hex(random_bytes(8));

            if (file_put_contents($temporaryPath, $payload.PHP_EOL) === false) {
                throw new RuntimeException('Unable to write temporary module runtime state.');
            }

            if (! chmod($temporaryPath, self::FILE_MODE)) {
                throw new RuntimeException('Unable to set temporary module runtime state permissions.');
            }

            if (! rename($temporaryPath, $this->path)) {
                throw new RuntimeException('Unable to replace module runtime state.');
            }

            $temporaryPath = null;
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }

            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
