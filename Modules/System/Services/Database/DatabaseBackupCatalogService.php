<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupCatalogService
{
    private const DIRECTORIES = ['private/backups', 'backups'];

    private const MAX_SCAN_FILES = 2000;

    public function listBackups(int $limit = 100): array
    {
        $limit = max(1, min($limit, self::MAX_SCAN_FILES));
        $files = [];

        foreach ($this->catalogFiles(['sql']) as $file) {
            $files[] = $this->descriptor($file['relative_path'], $file['absolute_path'], includePaths: false);
        }

        usort($files, static fn (array $left, array $right): int => $right['time'] <=> $left['time']);

        return array_slice($files, 0, $limit);
    }

    public function referenceForFileName(string $fileName, array $extensions = ['sql', 'zip']): ?string
    {
        $file = $this->findTrustedFileName($fileName, $extensions);

        return $file === null ? null : $this->reference($file['relative_path']);
    }

    public function resolveReference(string $reference, array $extensions = ['sql', 'zip']): ?array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $reference)) {
            return null;
        }

        foreach ($this->catalogFiles($extensions) as $file) {
            if (hash_equals($this->reference($file['relative_path']), $reference)) {
                return $this->descriptor($file['relative_path'], $file['absolute_path']);
            }
        }

        return null;
    }

    public function resolveTrustedFileName(string $fileName, array $extensions = ['sql', 'zip']): ?array
    {
        $file = $this->findTrustedFileName($fileName, $extensions);

        return $file === null ? null : $this->descriptor($file['relative_path'], $file['absolute_path']);
    }

    public function deleteReference(string $reference): int
    {
        $backup = $this->resolveReference($reference, ['sql']);

        if ($backup === null || ! Storage::disk('local')->delete($backup['relative_path'])) {
            throw new RuntimeException('Backup file not found.');
        }

        return 1;
    }

    public function isFullDatabaseBackup(string $path): bool
    {
        if (! is_readable($path) || filesize($path) < 100) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $sample = fread($handle, 1024 * 1024);
        } finally {
            fclose($handle);
        }

        return is_string($sample)
            && (str_contains($sample, 'MySQL dump') || str_contains($sample, 'MariaDB dump'))
            && str_contains($sample, 'DROP TABLE IF EXISTS')
            && substr_count($sample, 'CREATE TABLE') >= 2;
    }

    private function descriptor(string $relativePath, string $absolutePath, bool $includePaths = true): array
    {
        $fileName = basename($relativePath);

        $descriptor = [
            'id' => $this->reference($relativePath),
            'name' => $fileName,
            'path' => $this->reference($relativePath),
            'size' => (int) (filesize($absolutePath) ?: 0),
            'time' => (int) (filemtime($absolutePath) ?: 0),
            'is_full' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'sql'
                && $this->isFullDatabaseBackup($absolutePath),
        ];

        if ($includePaths) {
            $descriptor['relative_path'] = $relativePath;
            $descriptor['absolute_path'] = $absolutePath;
        }

        return $descriptor;
    }

    private function findTrustedFileName(string $fileName, array $extensions): ?array
    {
        if ($fileName !== basename($fileName) || ! $this->allowedFileName($fileName, $extensions)) {
            return null;
        }

        foreach (self::DIRECTORIES as $directory) {
            $relativePath = $directory.'/'.$fileName;

            if (Storage::disk('local')->exists($relativePath)) {
                return [
                    'relative_path' => $relativePath,
                    'absolute_path' => Storage::disk('local')->path($relativePath),
                ];
            }
        }

        return null;
    }

    private function catalogFiles(array $extensions): array
    {
        $files = [];

        foreach (self::DIRECTORIES as $directory) {
            foreach (Storage::disk('local')->files($directory) as $relativePath) {
                if (count($files) >= self::MAX_SCAN_FILES) {
                    return $files;
                }

                $fileName = basename($relativePath);

                if (! $this->allowedFileName($fileName, $extensions)) {
                    continue;
                }

                $files[] = [
                    'relative_path' => $relativePath,
                    'absolute_path' => Storage::disk('local')->path($relativePath),
                ];
            }
        }

        return $files;
    }

    private function allowedFileName(string $fileName, array $extensions): bool
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\.([A-Za-z0-9]+)\z/', $fileName, $matches)) {
            return false;
        }

        return in_array(strtolower($matches[1]), array_map('strtolower', $extensions), true);
    }

    private function reference(string $relativePath): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('Application key is required for backup references.');
        }

        return hash_hmac('sha256', 'system-local-backup|'.$relativePath, $key);
    }
}
